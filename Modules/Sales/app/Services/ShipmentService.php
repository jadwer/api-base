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
            throw new InvalidArgumentException("Shipment {$shipment->shipment_number} cannot be modified.");
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
            throw new InvalidArgumentException("Shipment must be shipped before marking as delivered.");
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
            throw new InvalidArgumentException("Cannot cancel a delivered shipment.");
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

            // Update order status
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
                "Order must be in confirmed, processing, or shipped status to create shipments. Current status: {$order->status}"
            );
        }
    }

    /**
     * Validate shipment items don't exceed remaining quantities.
     */
    protected function validateShipmentItems(SalesOrder $order, array $items): void
    {
        $order->load('items');

        // Get quantities already allocated in pending/processing shipments
        $pendingQuantities = ShipmentItem::whereHas('shipment', function ($q) use ($order) {
                $q->where('sales_order_id', $order->id)
                  ->whereIn('status', ['pending', 'processing']);
            })
            ->select('sales_order_item_id', DB::raw('SUM(quantity) as total_pending'))
            ->groupBy('sales_order_item_id')
            ->pluck('total_pending', 'sales_order_item_id');

        foreach ($items as $orderItemId => $quantity) {
            $orderItem = $order->items->find($orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException("Order item {$orderItemId} not found in order {$order->order_number}");
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException("Quantity must be greater than 0");
            }

            // remaining_quantity already subtracts shipped_quantity; also subtract pending shipment allocations
            $pendingQty = $pendingQuantities[$orderItemId] ?? 0;
            $effectiveRemaining = $orderItem->remaining_quantity - $pendingQty;

            if ($quantity > $effectiveRemaining) {
                throw new InvalidArgumentException(
                    "Cannot ship {$quantity} of item {$orderItemId}. Only {$effectiveRemaining} available (accounting for pending shipments)."
                );
            }
        }
    }

    /**
     * GAP-1: Deduct physical inventory when shipment is marked as shipped.
     * Decrements Stock.quantity and Stock.reserved_quantity.
     */
    protected function deductInventoryOnShip(Shipment $shipment): void
    {
        $warehouseId = $shipment->warehouse_id;

        foreach ($shipment->items as $shipmentItem) {
            $productId = $shipmentItem->salesOrderItem->product_id ?? null;
            if (!$productId) {
                continue;
            }

            try {
                $stockQuery = Stock::where('product_id', $productId);
                if ($warehouseId) {
                    $stockQuery->where('warehouse_id', $warehouseId);
                }
                $stock = $stockQuery->lockForUpdate()->first();

                if ($stock) {
                    $qty = $shipmentItem->quantity;
                    $stock->decrement('quantity', $qty);
                    // Release reservation (guard against going below zero)
                    $releaseQty = min($qty, $stock->reserved_quantity);
                    if ($releaseQty > 0) {
                        $stock->decrement('reserved_quantity', $releaseQty);
                    }

                    Log::info('Inventory deducted on shipment', [
                        'shipment_id' => $shipment->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'warehouse_id' => $warehouseId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to deduct inventory on shipment', [
                    'shipment_id' => $shipment->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * GAP-1: Restore inventory when a shipped shipment is cancelled.
     * Increments Stock.quantity and Stock.reserved_quantity back.
     */
    protected function restoreInventoryOnCancel(Shipment $shipment): void
    {
        $warehouseId = $shipment->warehouse_id;

        foreach ($shipment->items as $shipmentItem) {
            $productId = $shipmentItem->salesOrderItem->product_id ?? null;
            if (!$productId) {
                continue;
            }

            try {
                $stockQuery = Stock::where('product_id', $productId);
                if ($warehouseId) {
                    $stockQuery->where('warehouse_id', $warehouseId);
                }
                $stock = $stockQuery->lockForUpdate()->first();

                if ($stock) {
                    $qty = $shipmentItem->quantity;
                    $stock->increment('quantity', $qty);
                    $stock->increment('reserved_quantity', $qty);

                    Log::info('Inventory restored on shipment cancellation', [
                        'shipment_id' => $shipment->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'warehouse_id' => $warehouseId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to restore inventory on shipment cancel', [
                    'shipment_id' => $shipment->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update order status based on shipment status.
     */
    protected function updateOrderStatus(SalesOrder $order): void
    {
        $order->refresh();
        $fulfillmentStatus = $order->fulfillment_status;

        $targetStatus = match ($fulfillmentStatus) {
            'delivered' => 'delivered',
            'shipped' => 'shipped',
            'partially_shipped' => 'processing',
            default => null,
        };

        if ($targetStatus && $targetStatus !== $order->status
            && $this->orderStatusService->canTransitionTo($order, $targetStatus)) {
            $this->orderStatusService->updateStatus(
                $order,
                $targetStatus,
                "Auto-updated from shipment fulfillment ({$fulfillmentStatus})"
            );
        }
    }
}
