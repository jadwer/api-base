<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Modules\Inventory\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderStatusService
{
    private OrderNotificationService $notificationService;

    public function __construct(OrderNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Valid status transitions
     *
     * @var array
     */
    private array $validTransitions = [
        'draft' => ['pending', 'confirmed', 'cancelled'],
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['completed', 'returned'],
        'completed' => [],
        'cancelled' => [],
        'returned' => ['refunded'],
        'refunded' => [],
    ];

    /**
     * Update order status
     *
     * @param SalesOrder $order
     * @param string $newStatus
     * @param string|null $notes
     * @param array $metadata
     * @return SalesOrder
     */
    public function updateStatus(
        SalesOrder $order,
        string $newStatus,
        ?string $notes = null,
        array $metadata = []
    ): SalesOrder {
        // Validate transition
        if (!$this->canTransitionTo($order, $newStatus)) {
            throw new \Exception(
                "Cannot transition from '{$order->status}' to '{$newStatus}'"
            );
        }

        // GAP-2: If transitioning to 'delivered' and order has shipments,
        // at least one must be in 'delivered' status.
        // Orders without shipments can still be marked delivered (digital/services).
        if ($newStatus === 'delivered') {
            $totalShipped = $order->items->sum('shipped_quantity');
            if ($totalShipped > 0) {
                $hasDeliveredShipment = $order->shipments()
                    ->where('status', 'delivered')
                    ->exists();
                if (!$hasDeliveredShipment) {
                    throw new \Exception(
                        "Order has shipments but none are marked as delivered yet. Mark at least one shipment as delivered first."
                    );
                }
            }
        }

        $updated = DB::transaction(function () use ($order, $newStatus, $notes, $metadata) {
            $oldStatus = $order->status;

            // Build status history entry
            $history = $order->metadata['status_history'] ?? [];
            $history[] = [
                'from' => $oldStatus,
                'to' => $newStatus,
                'changed_at' => now()->toIso8601String(),
                'changed_by' => auth()->id() ?? 'system',
                'notes' => $notes,
                'metadata' => $metadata,
            ];

            // Update order status + history in single write
            $order->update([
                'status' => $newStatus,
                'metadata' => array_merge(
                    $order->metadata ?? [],
                    [
                        'last_status_change' => now()->toIso8601String(),
                        'status_history' => $history,
                    ]
                ),
            ]);

            // Trigger status-specific actions
            $this->handleStatusChange($order, $newStatus);

            // Send status update notification (async)
            if ($order->order_source === 'ecommerce') {
                $this->notificationService->sendOrderStatusUpdate($order, $oldStatus, $newStatus, $notes);
            }

            // Send specific notifications for certain statuses
            if ($newStatus === 'shipped' && $order->order_source === 'ecommerce') {
                $this->notificationService->sendShippingNotification($order);
            }

            return $order->fresh();
        });

        // QA post-commit (C1): marcar 'delivered' por este servicio (endpoint de status
        // del admin, POST /orders/{id}/status) debe facturar igual que la entrega por
        // remision. Antes el Observer neutralizado dejo este camino sin ARInvoice. El
        // evento se dispara DESPUES del commit (mismo patron que RemissionController::
        // deliver): un fallo del listener no revierte la entrega, y el listener es
        // idempotente por ar_invoice_id, asi que no duplica si la orden ya se facturo.
        if ($newStatus === 'delivered') {
            event(new \Modules\Sales\Events\SalesOrderDelivered($updated));
        }

        return $updated;
    }

    /**
     * Add entry to status history
     *
     * @param SalesOrder $order
     * @param string $oldStatus
     * @param string $newStatus
     * @param string|null $notes
     * @param array $metadata
     * @return void
     */
    public function addStatusHistory(
        SalesOrder $order,
        string $oldStatus,
        string $newStatus,
        ?string $notes = null,
        array $metadata = []
    ): void {
        $history = $order->metadata['status_history'] ?? [];

        $history[] = [
            'from' => $oldStatus,
            'to' => $newStatus,
            'changed_at' => now()->toIso8601String(),
            'changed_by' => auth()->id() ?? 'system',
            'notes' => $notes,
            'metadata' => $metadata,
        ];

        $order->update([
            'metadata' => array_merge(
                $order->metadata ?? [],
                ['status_history' => $history]
            ),
        ]);
    }

    /**
     * Get status history for order
     *
     * @param SalesOrder $order
     * @return array
     */
    public function getStatusHistory(SalesOrder $order): array
    {
        return $order->metadata['status_history'] ?? [];
    }

    /**
     * Check if order can transition to target status
     *
     * @param SalesOrder $order
     * @param string $targetStatus
     * @return bool
     */
    public function canTransitionTo(SalesOrder $order, string $targetStatus): bool
    {
        $currentStatus = $order->status;

        if (!isset($this->validTransitions[$currentStatus])) {
            return false;
        }

        return in_array($targetStatus, $this->validTransitions[$currentStatus]);
    }

    /**
     * Get available transitions for order
     *
     * @param SalesOrder $order
     * @return array
     */
    public function getAvailableTransitions(SalesOrder $order): array
    {
        $currentStatus = $order->status;

        return $this->validTransitions[$currentStatus] ?? [];
    }

    /**
     * Handle status-specific actions
     *
     * @param SalesOrder $order
     * @param string $newStatus
     * @return void
     */
    private function handleStatusChange(SalesOrder $order, string $newStatus): void
    {
        switch ($newStatus) {
            case 'confirmed':
                // SA-004: Reserve inventory when order confirmed
                $this->reserveInventory($order);
                break;

            case 'shipped':
                // Generate tracking number if not exists
                if (!$order->tracking_number) {
                    $order->update([
                        'tracking_number' => $this->generateTrackingNumber(),
                    ]);
                }
                break;

            case 'delivered':
                // Mark as delivered
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['delivered_at' => now()->toIso8601String()]
                    ),
                ]);

                // QA post-commit (C1): la entrega por este camino tambien descuenta
                // stock (antes solo la remision lo hacia; una orden entregada por el
                // endpoint de status quedaba sin salida ni COGS). Si la orden tiene
                // remisiones o shipments, esos flujos son duenos del stock y aqui no
                // se toca (evita doble descuento).
                $this->createDeliveryExitMovements($order);
                break;

            case 'completed':
                // Mark as completed
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['completed_at' => now()->toIso8601String()]
                    ),
                ]);
                break;

            case 'cancelled':
                // SA-004: Release inventory when order cancelled
                $this->releaseInventory($order);

                // Mark as cancelled
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['cancelled_at' => now()->toIso8601String()]
                    ),
                ]);

                // Refactor ciclo (Patron 2, P1): disparar el evento de dominio que ANTES
                // nunca se disparaba. Finance\SalesOrderCancelledListener anula la ARInvoice
                // y revierte el asiento GL. Sin esto, cancelar una venta facturada dejaba
                // ingresos y saldo AR inflados.
                event(new \Modules\Sales\Events\SalesOrderCancelled($order->fresh()));
                break;

            case 'returned':
                // QA post-commit (M1): la maquina prohibe delivered->cancelled; el camino
                // REAL para revertir una venta entregada es 'returned'. Antes este case no
                // existia: marcar returned no reponia stock ni anulaba la factura. Se
                // reutiliza la reversa de SalesOrderCancelledListener (anula ARInvoice,
                // revierte GL, repone el stock de las entregas) como PUENTE hasta que
                // exista un flujo formal de nota de credito/devolucion.
                event(new \Modules\Sales\Events\SalesOrderCancelled($order->fresh()));
                break;
        }
    }

    /**
     * QA post-commit (C1): salidas de inventario para ordenes entregadas por el
     * endpoint de status (sin remision ni shipment). Descuenta Stock.quantity via
     * InventoryMovementService::createExit, lo que postea el COGS por el evento
     * InventoryMovementCreated.
     *
     * Reglas:
     * - Si la orden tiene remisiones, la remision es duena del stock: no tocar.
     * - Si la orden tiene shipments shipped/delivered, ya descontaron directo: no tocar
     *   (la unificacion de ese camino es R9 del diseno).
     * - Idempotente por orden: si ya existen exits reference_type='sales_order' para
     *   esta orden, no re-descuenta (la maquina ademas impide re-entrar a delivered).
     * - Producto sin registro de Stock: se omite con log (ordenes de servicios/digital
     *   no llevan inventario). Stock existente pero insuficiente: lanza y revierte la
     *   transicion completa (mismo criterio que la entrega por remision).
     */
    private function createDeliveryExitMovements(SalesOrder $order): void
    {
        if ($order->remissions()->exists()) {
            return;
        }
        if ($order->shipments()->whereIn('status', ['shipped', 'delivered'])->exists()) {
            return;
        }

        $already = \Modules\Inventory\Models\InventoryMovement::where('reference_type', 'sales_order')
            ->where('reference_id', $order->id)
            ->exists();
        if ($already) {
            return;
        }

        $service = app(\Modules\Inventory\Services\InventoryMovementService::class);
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (!$item->product_id || $item->quantity <= 0) {
                continue;
            }

            $stock = \Modules\Inventory\Models\Stock::where('product_id', $item->product_id)
                ->orderByDesc('quantity')
                ->first();

            if (!$stock) {
                // Sin registro de stock: producto de servicio/digital. No bloquear la entrega.
                Log::info('Entrega sin registro de stock, se omite salida de inventario', [
                    'sales_order_id' => $order->id,
                    'product_id' => $item->product_id,
                ]);
                continue;
            }

            $service->createExit([
                'product_id' => $item->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'quantity' => $item->quantity,
                'movement_date' => now(),
                'reference_type' => 'sales_order',
                'reference_id' => $order->id,
                'description' => "Salida por entrega de orden {$order->order_number}",
                'user_id' => auth()->id() ?? \Modules\User\Models\User::first()?->id ?? 1,
                'metadata' => ['sales_order_item_id' => $item->id],
                // Exit del sistema: la entrega confirmada ES la validacion (IV-009).
                'quality_checked' => true,
            ]);
        }
    }

    /**
     * Mark order as shipped
     *
     * @param SalesOrder $order
     * @param string|null $trackingNumber
     * @param string|null $trackingUrl
     * @param string|null $carrier
     * @return SalesOrder
     */
    public function markAsShipped(
        SalesOrder $order,
        ?string $trackingNumber = null,
        ?string $trackingUrl = null,
        ?string $carrier = null
    ): SalesOrder {
        $metadata = [];

        if ($carrier) {
            $metadata['carrier'] = $carrier;
        }

        $order->update([
            'tracking_number' => $trackingNumber ?? $this->generateTrackingNumber(),
            'tracking_url' => $trackingUrl,
        ]);

        return $this->updateStatus($order, 'shipped', 'Order shipped', $metadata);
    }

    /**
     * Mark order as delivered
     *
     * @param SalesOrder $order
     * @param string|null $notes
     * @return SalesOrder
     */
    public function markAsDelivered(SalesOrder $order, ?string $notes = null): SalesOrder
    {
        return $this->updateStatus($order, 'delivered', $notes ?? 'Order delivered successfully');
    }

    /**
     * Cancel order
     *
     * @param SalesOrder $order
     * @param string|null $reason
     * @return SalesOrder
     */
    public function cancelOrder(SalesOrder $order, ?string $reason = null): SalesOrder
    {
        if (!$this->canTransitionTo($order, 'cancelled')) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        return $this->updateStatus($order, 'cancelled', $reason ?? 'Order cancelled by customer');
    }

    /**
     * Generate tracking number
     *
     * @return string
     */
    private function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Reserve inventory for sales order (SA-004)
     *
     * Increments Stock.reserved_quantity and decrements Stock.quantity
     * when order is confirmed.
     *
     * @param SalesOrder $order
     * @return void
     */
    private function reserveInventory(SalesOrder $order): void
    {
        // Load items if not already loaded
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        // Get warehouse ID from order metadata or use default
        $warehouseId = $order->metadata['warehouse_id'] ?? null;

        foreach ($order->items as $item) {
            try {
                // Find stock record
                $stockQuery = Stock::where('product_id', $item->product_id);

                if ($warehouseId) {
                    $stockQuery->where('warehouse_id', $warehouseId);
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if (!$stock) {
                    Log::warning('Stock not found for reservation', [
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                    ]);
                    continue;
                }

                // Check if sufficient quantity available (quantity - reserved = available)
                $availableQty = $stock->quantity - $stock->reserved_quantity;
                if ($availableQty < $item->quantity) {
                    Log::error('Insufficient stock for reservation', [
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'required' => $item->quantity,
                        'available' => $availableQty,
                    ]);
                    throw new \Exception("Insufficient stock for product ID: {$item->product_id}");
                }

                // Reserve inventory: only increment reserved_quantity
                // available_quantity is a generated column (quantity - reserved_quantity)
                $stock->increment('reserved_quantity', $item->quantity);

                Log::info('Inventory reserved for sales order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'stock_id' => $stock->id,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to reserve inventory for sales order', [
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);

                // Re-throw to rollback transaction
                throw $e;
            }
        }
    }

    /**
     * Release inventory reservation for sales order (SA-004)
     *
     * Decrements Stock.reserved_quantity and increments Stock.quantity
     * when order is cancelled.
     *
     * @param SalesOrder $order
     * @return void
     */
    private function releaseInventory(SalesOrder $order): void
    {
        // Load items if not already loaded
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        // Get warehouse ID from order metadata or use default
        $warehouseId = $order->metadata['warehouse_id'] ?? null;

        foreach ($order->items as $item) {
            try {
                // Find stock record
                $stockQuery = Stock::where('product_id', $item->product_id);

                if ($warehouseId) {
                    $stockQuery->where('warehouse_id', $warehouseId);
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if (!$stock) {
                    Log::warning('Stock not found for release', [
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                    ]);
                    continue;
                }

                // Release reservation: only decrement reserved_quantity
                // Guard against going below zero (e.g. order was never fully reserved)
                $releaseQty = min($item->quantity, $stock->reserved_quantity);
                if ($releaseQty > 0) {
                    $stock->decrement('reserved_quantity', $releaseQty);
                }

                Log::info('Inventory reservation released for sales order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'stock_id' => $stock->id,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to release inventory for sales order', [
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);

                // Log error but don't throw - allow cancellation to proceed
            }
        }
    }
}
