<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\Shipment;
use Modules\Sales\Models\ShipmentItem;
use Modules\Inventory\Models\Stock;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * SA-M001: Service for managing partial shipments.
 */
class ShipmentService
{
    private OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    /**
     * Create a new shipment for an order.
     *
     * @param SalesOrder $order
     * @param array $items Array of ['sales_order_item_id' => quantity]
     * @param array $shipmentData Optional shipment data (carrier, tracking, etc.)
     * @return Shipment
     * @throws InvalidArgumentException
     */
    public function createShipment(SalesOrder $order, array $items, array $shipmentData = []): Shipment
    {
        $this->validateOrderForShipment($order);
        $this->validateShipmentItems($order, $items);

        return DB::transaction(function () use ($order, $items, $shipmentData) {
            // Create shipment
            $shipment = Shipment::create([
                'sales_order_id' => $order->id,
                'warehouse_id' => $shipmentData['warehouse_id'] ?? null,
                'shipment_number' => Shipment::generateShipmentNumber(),
                'status' => $shipmentData['status'] ?? 'pending',
                'carrier' => $shipmentData['carrier'] ?? null,
                'tracking_number' => $shipmentData['tracking_number'] ?? null,
                'tracking_url' => $shipmentData['tracking_url'] ?? null,
                'ship_date' => $shipmentData['ship_date'] ?? null,
                'estimated_delivery' => $shipmentData['estimated_delivery'] ?? null,
                'shipping_address' => $shipmentData['shipping_address'] ?? $order->shipping_address,
                'notes' => $shipmentData['notes'] ?? null,
                'shipping_cost' => $shipmentData['shipping_cost'] ?? 0,
                'weight' => $shipmentData['weight'] ?? null,
                'metadata' => $shipmentData['metadata'] ?? null,
            ]);

            // Create shipment items
            foreach ($items as $orderItemId => $quantity) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'sales_order_item_id' => $orderItemId,
                    'quantity' => $quantity,
                ]);
            }

            return $shipment;
        });
    }

    /**
     * Mark shipment as shipped and update order items.
     */
    public function markAsShipped(Shipment $shipment, ?string $trackingNumber = null, ?string $carrier = null): Shipment
    {
        if (!$shipment->isEditable()) {
            throw new InvalidArgumentException("El envío {$shipment->shipment_number} ya no se puede modificar.");
        }

        return DB::transaction(function () use ($shipment, $trackingNumber, $carrier) {
            $shipment->update([
                'status' => 'shipped',
                'ship_date' => now(),
                'tracking_number' => $trackingNumber ?? $shipment->tracking_number,
                'carrier' => $carrier ?? $shipment->carrier,
            ]);

            // Update shipped quantities on order items
            foreach ($shipment->items as $shipmentItem) {
                $orderItem = $shipmentItem->salesOrderItem;
                $orderItem->shipped_quantity += $shipmentItem->quantity;
                $orderItem->updateFulfillmentStatus();
            }

            // GAP-1: Deduct physical inventory and release reservation on ship
            $this->deductInventoryOnShip($shipment);

            // Update order status if fully shipped
            $this->updateOrderStatus($shipment->salesOrder);

            return $shipment->fresh();
        });
    }

    /**
     * Mark shipment as delivered.
     */
    public function markAsDelivered(Shipment $shipment, ?string $actualDelivery = null): Shipment
    {
        if (!in_array($shipment->status, ['shipped', 'processing'])) {
            throw new InvalidArgumentException("El envío debe estar enviado antes de marcarlo como entregado.");
        }

        return DB::transaction(function () use ($shipment, $actualDelivery) {
            $shipment->update([
                'status' => 'delivered',
                'actual_delivery' => $actualDelivery ?? now(),
            ]);

            // Update order item statuses to delivered
            foreach ($shipment->items as $shipmentItem) {
                $orderItem = $shipmentItem->salesOrderItem;
                if ($orderItem->isFullyShipped()) {
                    $orderItem->update(['fulfillment_status' => 'delivered']);
                }
            }

            // Update order status
            $this->updateOrderStatus($shipment->salesOrder);

            return $shipment->fresh();
        });
    }

    /**
     * Cancel a shipment.
     */
    public function cancelShipment(Shipment $shipment, ?string $reason = null): Shipment
    {
        if ($shipment->status === 'delivered') {
            throw new InvalidArgumentException("No se puede cancelar un envío entregado.");
        }

        return DB::transaction(function () use ($shipment, $reason) {
            // If shipment was already shipped, revert the shipped quantities and restore inventory
            if ($shipment->status === 'shipped') {
                foreach ($shipment->items as $shipmentItem) {
                    $orderItem = $shipmentItem->salesOrderItem;
                    $orderItem->shipped_quantity = max(0, $orderItem->shipped_quantity - $shipmentItem->quantity);
                    $orderItem->updateFulfillmentStatus();
                }

                // GAP-1: Restore inventory that was deducted on ship
                $this->restoreInventoryOnCancel($shipment);
            }

            $shipment->update([
                'status' => 'cancelled',
                'metadata' => array_merge($shipment->metadata ?? [], [
                    'cancelled_at' => now()->toIso8601String(),
                    'cancellation_reason' => $reason,
                ]),
            ]);

            // Surtido F3: si la mercancia regreso, el pedido retrocede a lo que
            // sigue fuera (en proceso o confirmado); si no, avanza como antes.
            $this->orderStatusService->rewindAfterReturn(
                $shipment->salesOrder->fresh(),
                "Envio {$shipment->shipment_number} cancelado"
            );
            $this->updateOrderStatus($shipment->salesOrder);

            return $shipment->fresh();
        });
    }

    /**
     * Get shipment summary for an order.
     */
    public function getOrderShipmentSummary(SalesOrder $order): array
    {
        $order->load(['items', 'shipments.items']);

        $summary = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'fulfillment_status' => $order->fulfillment_status,
            'total_items' => $order->items->count(),
            'total_quantity' => $order->items->sum('quantity'),
            'shipped_quantity' => $order->items->sum('shipped_quantity'),
            'remaining_quantity' => $order->remaining_to_ship,
            'shipments_count' => $order->shipments->count(),
            'shipments' => $order->shipments->map(fn($s) => [
                'id' => $s->id,
                'shipment_number' => $s->shipment_number,
                'status' => $s->status,
                'carrier' => $s->carrier,
                'tracking_number' => $s->tracking_number,
                'ship_date' => $s->ship_date?->toDateString(),
                'items_count' => $s->items->count(),
                'total_quantity' => $s->items->sum('quantity'),
            ]),
            'items' => $order->items->map(fn($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'shipped_quantity' => $i->shipped_quantity,
                'remaining_quantity' => $i->remaining_quantity,
                'fulfillment_status' => $i->fulfillment_status,
            ]),
        ];

        return $summary;
    }

    /**
     * Validate order can have shipments created.
     */
    protected function validateOrderForShipment(SalesOrder $order): void
    {
        $validStatuses = ['confirmed', 'processing', 'shipped'];

        if (!in_array($order->status, $validStatuses)) {
            throw new InvalidArgumentException(
                "El pedido debe estar confirmado, en proceso o enviado para crear envíos. Estado actual: {$order->status}."
            );
        }
    }

    /**
     * Validate shipment items don't exceed remaining quantities.
     */
    protected function validateShipmentItems(SalesOrder $order, array $items): void
    {
        $order->load('items');

        foreach ($items as $orderItemId => $quantity) {
            /** @var SalesOrderItem|null $orderItem */
            $orderItem = $order->items->find($orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException("La partida {$orderItemId} no pertenece al pedido {$order->order_number}.");
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException("La cantidad debe ser mayor a 0.");
            }

            // Surtido F3: lo pendiente descuenta remisiones vivas, lo embarcado y
            // los envios abiertos (regla de coordinacion envio/remision).
            $pending = $orderItem->pendingOutboundQuantity();
            if ($quantity > $pending) {
                throw new InvalidArgumentException(
                    "No se pueden enviar {$quantity} de la partida {$orderItemId}. Solo quedan {$pending} por surtir (contando remisiones y envíos abiertos)."
                );
            }
        }
    }

    /**
     * Surtido F3 (puntos 4/5): embarcar da salida de inventario con movimiento y
     * costo de venta (createExit postea el COGS via InventoryMovementCreated),
     * igual que la remision. Idempotente por partida con la llave
     * "shipment:{id}:item:{itemId}". Lo que sale deja de estar apartado.
     * Antes descontaba Stock.quantity directo (sin movimiento ni COGS) y se
     * saltaba todo si el pedido tenia remisiones; ahora la coordinacion vive en
     * lo pendiente por partida (validateShipmentItems).
     *
     * @throws \Throwable si no hay existencia: la transaccion de markAsShipped revierte.
     */
    protected function deductInventoryOnShip(Shipment $shipment): void
    {
        $service = app(\Modules\Inventory\Services\InventoryMovementService::class);
        /** @var SalesOrder $order */
        $order = $shipment->salesOrder;

        foreach ($shipment->items as $shipmentItem) {
            /** @var ShipmentItem $shipmentItem */
            /** @var SalesOrderItem|null $orderItem */
            $orderItem = $shipmentItem->salesOrderItem;
            if (!$orderItem || !$orderItem->product_id || $shipmentItem->quantity <= 0) {
                continue;
            }

            $warehouseId = $this->resolveWarehouseId($shipment, $order, $orderItem);
            if (!$warehouseId) {
                // Sin registro de stock: servicio o producto digital (mismo criterio
                // que la entrega por status). Con stock insuficiente, createExit lanza.
                Log::info('Envio sin registro de stock, se omite salida de inventario', [
                    'shipment_id' => $shipment->id,
                    'product_id' => $orderItem->product_id,
                ]);
                continue;
            }

            $service->createExit([
                'product_id' => $orderItem->product_id,
                'warehouse_id' => $warehouseId,
                'quantity' => $shipmentItem->quantity,
                'movement_date' => now(),
                'reference_type' => 'shipment',
                'reference_id' => $shipment->id,
                'idempotency_key' => "shipment:{$shipment->id}:item:{$shipmentItem->id}",
                'description' => "Salida por envío {$shipment->shipment_number}",
                'user_id' => auth()->id() ?? \Modules\User\Models\User::first()->id ?? 1,
                'unit_cost' => (float) (\Modules\Product\Models\Product::find($orderItem->product_id)->cost ?? 0),
                'metadata' => ['shipment_item_id' => $shipmentItem->id, 'sales_order_item_id' => $orderItem->id],
            ]);

            $this->orderStatusService->releaseItemReservation($order, $orderItem->id, (float) $shipmentItem->quantity);
        }
    }

    /**
     * Almacen de salida: el del envio -> donde el pedido aparto la partida -> el
     * de mas existencia libre (misma resolucion que la remision).
     */
    private function resolveWarehouseId(Shipment $shipment, SalesOrder $order, SalesOrderItem $orderItem): ?int
    {
        if ($shipment->warehouse_id) {
            return (int) $shipment->warehouse_id;
        }

        $stockId = $this->orderStatusService->reservationStockId($order, $orderItem->id);
        if ($stockId && ($reserved = Stock::find($stockId))) {
            return (int) $reserved->warehouse_id;
        }

        $warehouseId = Stock::where('product_id', $orderItem->product_id)
            ->orderByRaw('(quantity - reserved_quantity) desc')
            ->value('warehouse_id');

        return $warehouseId ? (int) $warehouseId : null;
    }

    /**
     * Surtido F3: cancelar un envio embarcado repone con una entrada por cada
     * salida (revierte tambien el COGS) y vuelve a apartar la mercancia para el
     * pedido. La llave "sales_cancel:exit:{id}" es la misma que usa la
     * cancelacion del pedido: si despues se cancela la venta, esa salida no se
     * repone dos veces.
     *
     * Envios embarcados antes de F3 no tienen salida registrada (descontaron
     * Stock.quantity directo): para ellos se conserva la reposicion directa.
     */
    protected function restoreInventoryOnCancel(Shipment $shipment): void
    {
        $exits = \Modules\Inventory\Models\InventoryMovement::where('movement_type', 'exit')
            ->where('reference_type', 'shipment')
            ->where('reference_id', $shipment->id)
            ->get();

        if ($exits->isEmpty()) {
            $this->restoreLegacyShipment($shipment);
            return;
        }

        $service = app(\Modules\Inventory\Services\InventoryMovementService::class);
        /** @var SalesOrder $order */
        $order = $shipment->salesOrder;

        foreach ($exits as $exit) {
            /** @var \Modules\Inventory\Models\InventoryMovement $exit */
            $service->createEntry([
                'product_id' => $exit->product_id,
                'warehouse_id' => $exit->warehouse_id,
                'quantity' => $exit->quantity,
                'unit_cost' => $exit->unit_cost,
                'reference_type' => 'sales_cancel',
                'reference_id' => $exit->id,
                'idempotency_key' => "sales_cancel:exit:{$exit->id}",
                'movement_date' => now(),
                'description' => "Reposición por cancelación del envío {$shipment->shipment_number}",
                'user_id' => auth()->id() ?? \Modules\User\Models\User::first()->id ?? 1,
                'metadata' => [
                    'shipment_id' => $shipment->id,
                    'reversed_exit_movement_id' => $exit->id,
                    'reversal' => true,
                ],
            ]);

            $orderItemId = (int) ($exit->metadata['sales_order_item_id'] ?? 0);
            if ($orderItemId && !in_array($order->status, ['cancelled', 'returned', 'refunded'], true)) {
                $this->orderStatusService->restoreItemReservation($order, $orderItemId, (float) $exit->quantity, (int) $exit->warehouse_id);
            }
        }
    }

    /**
     * Reposicion directa para envios embarcados antes de F3 (sin movimiento).
     */
    private function restoreLegacyShipment(Shipment $shipment): void
    {
        $warehouseId = $shipment->warehouse_id;

        foreach ($shipment->items as $shipmentItem) {
            $productId = $shipmentItem->salesOrderItem->product_id ?? null;
            if (!$productId) {
                continue;
            }

            $stock = Stock::where('product_id', $productId)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->increment('quantity', $shipmentItem->quantity);
                $stock->increment('reserved_quantity', $shipmentItem->quantity);
            }
        }
    }

    /**
     * Update order status based on shipment status.
     */
    protected function updateOrderStatus(SalesOrder $order): void
    {
        $order->refresh();

        // Surtido F3: el avance cuenta remisiones entregadas y envios embarcados
        // (antes solo envios: un pedido surtido mitad y mitad nunca se entregaba).
        $order->load('items');
        $allOut = $order->items->every(fn ($i) => $i instanceof SalesOrderItem && $i->notYetOutQuantity() <= 0);
        $anyOut = $order->items->contains(fn ($i) => $i instanceof SalesOrderItem && $i->notYetOutQuantity() < (float) $i->quantity);
        $openDocs = $order->remissions()->whereIn('status', ['draft', 'printed'])->exists()
            || $order->shipments()->whereIn('status', ['pending', 'processing', 'shipped'])->exists();

        $targetStatus = match (true) {
            $allOut && !$openDocs => 'delivered',
            $allOut => 'shipped',
            $anyOut => 'processing',
            default => null,
        };

        if (!$targetStatus || !in_array($order->status, ['confirmed', 'processing', 'shipped'], true)) {
            return;
        }

        $this->orderStatusService->advanceTo(
            $order,
            $targetStatus,
            "Avance automatico por surtido ({$targetStatus})"
        );
    }
}
