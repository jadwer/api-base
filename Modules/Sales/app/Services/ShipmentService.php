<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\Shipment;
use Modules\Sales\Models\ShipmentItem;
use InvalidArgumentException;

/**
 * SA-M001: Service for managing partial shipments.
 */
class ShipmentService
{
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
            // If shipment was already shipped, revert the shipped quantities
            if ($shipment->status === 'shipped') {
                foreach ($shipment->items as $shipmentItem) {
                    $orderItem = $shipmentItem->salesOrderItem;
                    $orderItem->shipped_quantity = max(0, $orderItem->shipped_quantity - $shipmentItem->quantity);
                    $orderItem->updateFulfillmentStatus();
                }
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

        foreach ($items as $orderItemId => $quantity) {
            $orderItem = $order->items->find($orderItemId);

            if (!$orderItem) {
                throw new InvalidArgumentException("Order item {$orderItemId} not found in order {$order->order_number}");
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException("Quantity must be greater than 0");
            }

            $remaining = $orderItem->remaining_quantity;
            if ($quantity > $remaining) {
                throw new InvalidArgumentException(
                    "Cannot ship {$quantity} of item {$orderItemId}. Only {$remaining} remaining."
                );
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

        // Update order metadata with shipment info
        $metadata = $order->metadata ?? [];

        if ($fulfillmentStatus === 'delivered') {
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'metadata' => array_merge($metadata, ['delivery_completed_at' => now()->toIso8601String()]),
            ]);
        } elseif ($fulfillmentStatus === 'shipped') {
            $order->update([
                'status' => 'shipped',
                'metadata' => array_merge($metadata, ['fully_shipped_at' => now()->toIso8601String()]),
            ]);
        } elseif ($fulfillmentStatus === 'partially_shipped') {
            $order->update([
                'status' => 'processing',
                'metadata' => array_merge($metadata, ['partially_shipped' => true]),
            ]);
        }
    }
}
