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
        array $metadata = [],
        array $options = []
    ): SalesOrder {
        // Opciones (surtido F2): notify=false calla los correos de pasos intermedios;
        // dispatch_delivered=false deja el evento de facturacion al llamador (la
        // entrega por remision lo dispara tras SU commit); skip_shipment_guard
        // omite GAP-2 cuando la prueba de entrega es la remision.
        $notify = $options['notify'] ?? true;
        $dispatchDelivered = $options['dispatch_delivered'] ?? true;

        // Validate transition
        if (!$this->canTransitionTo($order, $newStatus)) {
            throw new \Exception(
                "No se puede cambiar el pedido de '{$order->status}' a '{$newStatus}'."
            );
        }

        // GAP-2: If transitioning to 'delivered' and order has shipments,
        // at least one must be in 'delivered' status.
        // Orders without shipments can still be marked delivered (digital/services).
        if ($newStatus === 'delivered' && empty($options['skip_shipment_guard'])) {
            $totalShipped = $order->items->sum('shipped_quantity');
            if ($totalShipped > 0) {
                $hasDeliveredShipment = $order->shipments()
                    ->where('status', 'delivered')
                    ->exists();
                if (!$hasDeliveredShipment) {
                    throw new \Exception(
                        "El pedido tiene envíos pero ninguno está entregado. Marca al menos un envío como entregado primero."
                    );
                }
            }
        }

        // Surtido F3: marcar entregado a mano con remisiones o envios abiertos dejaria
        // esos documentos colgados (y su mercancia sin salida coherente).
        if ($newStatus === 'delivered' && empty($options['skip_shipment_guard'])) {
            $openRemissions = $order->remissions()->whereIn('status', ['draft', 'printed'])->exists();
            $openShipments = $order->shipments()->whereIn('status', ['pending', 'processing'])->exists();
            if ($openRemissions || $openShipments) {
                throw new \Exception(
                    'El pedido tiene remisiones o envíos abiertos. Entrégalos o cancélalos antes de marcarlo como entregado.'
                );
            }
        }

        $updated = DB::transaction(function () use ($order, $newStatus, $notes, $metadata, $notify) {
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
            if ($notify && $order->order_source === 'ecommerce') {
                $this->notificationService->sendOrderStatusUpdate($order, $oldStatus, $newStatus, $notes);
            }

            // Send specific notifications for certain statuses
            if ($notify && $newStatus === 'shipped' && $order->order_source === 'ecommerce') {
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
        if ($newStatus === 'delivered' && $dispatchDelivered) {
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
     * Surtido F2: avanza el pedido hasta $target recorriendo la maquina de estados
     * (confirmed -> processing -> shipped -> delivered) en vez de escribir el status
     * directo. Cada paso deja su historial y sus efectos; los correos solo salen en
     * el ultimo paso. Si el pedido ya esta en $target o mas adelante, no hace nada.
     */
    public function advanceTo(SalesOrder $order, string $target, ?string $notes = null, array $options = []): SalesOrder
    {
        $path = ['draft', 'pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $from = array_search($order->status, $path, true);
        $to = array_search($target, $path, true);

        if ($from === false || $to === false) {
            throw new \Exception("No se puede llevar el pedido de '{$order->status}' a '{$target}'.");
        }

        for ($i = $from + 1; $i <= $to; $i++) {
            $step = $path[$i];
            if (!$this->canTransitionTo($order, $step)) {
                continue; // draft -> confirmed salta pending
            }
            $order = $this->updateStatus($order, $step, $notes, [], array_merge($options, [
                'notify' => ($options['notify'] ?? true) && $step === $target,
            ]));
        }

        return $order;
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

                // Fase 2.7: liberar la reserva del confirm. Sin esto, el exit fisico
                // descuenta quantity con reserved_quantity aun viva y available
                // queda descontado doble para siempre (fuga cazada por el test de
                // invariante del ciclo).
                $this->releaseInventory($order);
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

                // Surtido F4: lo que esperaba en backorder ya no se surte.
                \Modules\Sales\Models\Backorder::where('sales_order_id', $order->id)
                    ->pending()
                    ->update(['status' => 'cancelled']);

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
     * - Surtido F3: sale solo lo que no salio por remision entregada ni por envio
     *   embarcado (notYetOutQuantity); con remisiones o envios abiertos la entrega
     *   manual se rechaza antes (updateStatus).
     * - Idempotente por linea via idempotency_key "sales_order:{id}:item:{itemId}"
     *   con UNIQUE en BD (R1); la maquina ademas impide re-entrar a delivered.
     * - Producto sin registro de Stock: se omite con log (ordenes de servicios/digital
     *   no llevan inventario). Stock existente pero insuficiente: lanza y revierte la
     *   transicion completa (mismo criterio que la entrega por remision).
     */
    private function createDeliveryExitMovements(SalesOrder $order): void
    {
        $service = app(\Modules\Inventory\Services\InventoryMovementService::class);
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            /** @var \Modules\Sales\Models\SalesOrderItem $item */
            // Surtido F3: solo sale lo que no salio por remision ni por envio.
            $quantity = $item->notYetOutQuantity();
            if (!$item->product_id || $quantity <= 0) {
                continue;
            }

            $reservedStockId = $this->reservationStockId($order, $item->id);
            $stock = ($reservedStockId ? Stock::find($reservedStockId) : null)
                ?? Stock::where('product_id', $item->product_id)
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
                'quantity' => $quantity,
                'movement_date' => now(),
                'reference_type' => 'sales_order',
                'reference_id' => $order->id,
                'idempotency_key' => "sales_order:{$order->id}:item:{$item->id}",
                'description' => "Salida por entrega de orden {$order->order_number}",
                'user_id' => auth()->id() ?? \Modules\User\Models\User::first()?->id ?? 1,
                'metadata' => ['sales_order_item_id' => $item->id],
                // R4: exencion de IV-009 por origen (reference_type='sales_order').
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
            throw new \Exception('El pedido no se puede cancelar en su estado actual.');
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
     * Incrementa Stock.reserved_quantity por partida y registra lo apartado en
     * metadata.reservations ({item_id: {stock_id, quantity}}). Surtido F2 (punto 3):
     * con ese registro cada liberacion descuenta solo lo que ESTE pedido aparto;
     * antes se liberaba min(cantidad, reservado del stock) y un pedido podia soltar
     * la reserva de otro. Surtido F4: si no alcanza, aparta lo que hay y el
     * faltante queda en backorder.
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
        $reservations = [];

        foreach ($order->items as $item) {
            /** @var \Modules\Sales\Models\SalesOrderItem $item */
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

            // Surtido F4: si no alcanza, se aparta lo que hay y el faltante queda en
            // backorder (se surte por remision cuando llega la mercancia). Antes la
            // confirmacion se rechazaba con "Existencia insuficiente".
            $availableQty = max(0.0, (float) $stock->quantity - (float) $stock->reserved_quantity);
            $reserveQty = min((float) $item->quantity, $availableQty);
            $shortQty = round((float) $item->quantity - $reserveQty, 4);

            // Reserve inventory: only increment reserved_quantity
            // available_quantity is a generated column (quantity - reserved_quantity)
            if ($reserveQty > 0) {
                $stock->increment('reserved_quantity', $reserveQty);
            }
            $reservations[(string) $item->id] = [
                'stock_id' => $stock->id,
                'quantity' => $reserveQty,
            ];

            if ($shortQty > 0) {
                app(BackorderService::class)->createBackorder($item, $shortQty, $stock->warehouse_id, [
                    'notes' => "Faltante al confirmar el pedido {$order->order_number}",
                ]);
            }
        }

        $order->update([
            'metadata' => array_merge($order->metadata ?? [], ['reservations' => $reservations]),
        ]);
    }

    /**
     * Stock donde quedo apartada la partida (null si no se aparto).
     */
    public function reservationStockId(SalesOrder $order, int $itemId): ?int
    {
        $entry = $order->metadata['reservations'][(string) $itemId] ?? null;

        return $entry && ($entry['quantity'] ?? 0) > 0 ? (int) $entry['stock_id'] : null;
    }

    /**
     * Libera hasta $quantity de la reserva de UNA partida (la parte que ya salio
     * por remision o envio). Devuelve lo liberado. Pedidos confirmados antes del
     * registro (sin metadata.reservations) no liberan aqui: los cubre
     * releaseInventory al entregar o cancelar, con el criterio anterior.
     */
    public function releaseItemReservation(SalesOrder $order, int $itemId, float $quantity): float
    {
        $key = (string) $itemId;
        $reservations = $order->metadata['reservations'] ?? null;
        $entry = $reservations[$key] ?? null;

        if (!$entry || ($entry['quantity'] ?? 0) <= 0 || $quantity <= 0) {
            return 0.0;
        }

        $stock = Stock::whereKey($entry['stock_id'])->lockForUpdate()->first();
        $release = min($quantity, (float) $entry['quantity']);
        if ($stock) {
            $release = min($release, (float) $stock->reserved_quantity);
            if ($release > 0) {
                $stock->decrement('reserved_quantity', $release);
            }
        }

        $reservations[$key]['quantity'] = round((float) $entry['quantity'] - min($quantity, (float) $entry['quantity']), 4);
        $order->update([
            'metadata' => array_merge($order->metadata ?? [], ['reservations' => $reservations]),
        ]);

        return $release;
    }

    /**
     * Surtido F3: vuelve a apartar mercancia que regreso al almacen (envio
     * cancelado) para un pedido que sigue vivo. Solo aplica a pedidos con
     * registro de reservas; aparta en el stock del almacen donde se repuso.
     */
    public function restoreItemReservation(SalesOrder $order, int $itemId, float $quantity, int $warehouseId): void
    {
        $reservations = $order->metadata['reservations'] ?? null;
        $item = $order->items()->find($itemId);
        if (!is_array($reservations) || !$item || $quantity <= 0) {
            return;
        }

        $stock = Stock::where('product_id', $item->product_id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();
        if (!$stock) {
            return;
        }

        $key = (string) $itemId;
        $current = (float) ($reservations[$key]['quantity'] ?? 0);
        if ($current > 0 && (int) ($reservations[$key]['stock_id'] ?? 0) !== $stock->id) {
            // La partida sigue apartada en otro almacen: un registro por partida no
            // puede llevar dos stocks; la mercancia repuesta queda libre.
            Log::warning('Reserva no restaurada: la partida esta apartada en otro almacen', [
                'order_id' => $order->id, 'item_id' => $itemId, 'warehouse_id' => $warehouseId,
            ]);
            return;
        }
        $stock->increment('reserved_quantity', $quantity);
        $reservations[$key] = ['stock_id' => $stock->id, 'quantity' => round($current + $quantity, 4)];

        $order->update([
            'metadata' => array_merge($order->metadata ?? [], ['reservations' => $reservations]),
        ]);
    }

    /**
     * Release inventory reservation for sales order (SA-004)
     *
     * Solo decrementa Stock.reserved_quantity (quantity no se toca aqui; la
     * reponen o descuentan los movimientos de inventario). Se llama al cancelar
     * Y al entregar: si la reserva del confirm no se libera en la entrega, el
     * exit fisico descuenta quantity con la reserva aun viva y available
     * (quantity - reserved) descuenta doble para siempre.
     *
     * Con metadata.reservations libera solo lo pendiente de cada partida (lo ya
     * liberado por remisiones o envios no se suelta dos veces). Sin registro
     * (pedidos confirmados antes de F2) conserva el criterio anterior.
     *
     * @param SalesOrder $order
     * @return void
     */
    public function releaseInventory(SalesOrder $order): void
    {
        // Load items if not already loaded
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        if (is_array($order->metadata['reservations'] ?? null)) {
            foreach (array_keys($order->metadata['reservations']) as $itemId) {
                $pending = (float) ($order->metadata['reservations'][$itemId]['quantity'] ?? 0);
                $this->releaseItemReservation($order, (int) $itemId, $pending);
            }
            return;
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
