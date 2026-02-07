<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Stock;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use InvalidArgumentException;

/**
 * SA-M002: Service for managing backorders.
 */
class BackorderService
{
    protected ShipmentService $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * Create a backorder for an order item with insufficient stock.
     *
     * @param SalesOrderItem $orderItem
     * @param float $backorderQuantity Quantity that cannot be fulfilled
     * @param int|null $warehouseId Expected warehouse for fulfillment
     * @param array $data Additional backorder data
     * @return Backorder
     */
    public function createBackorder(
        SalesOrderItem $orderItem,
        float $backorderQuantity,
        ?int $warehouseId = null,
        array $data = []
    ): Backorder {
        if ($backorderQuantity <= 0) {
            throw new InvalidArgumentException('Backorder quantity must be greater than 0');
        }

        // Check if backorder already exists for this item (with lock to prevent race condition)
        $existing = Backorder::where('sales_order_item_id', $orderItem->id)
            ->pending()
            ->lockForUpdate()
            ->first();

        if ($existing) {
            // Update existing backorder quantity
            $existing->backorder_quantity += $backorderQuantity;
            $existing->save();
            return $existing;
        }

        return Backorder::create([
            'sales_order_id' => $orderItem->sales_order_id,
            'sales_order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'warehouse_id' => $warehouseId,
            'backorder_number' => Backorder::generateBackorderNumber(),
            'original_quantity' => $orderItem->quantity,
            'backorder_quantity' => $backorderQuantity,
            'fulfilled_quantity' => 0,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'expected_date' => $data['expected_date'] ?? null,
            'promised_date' => $data['promised_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Check stock availability and create backorders for insufficient items.
     *
     * @param SalesOrder $order
     * @param int|null $warehouseId Warehouse to check stock in
     * @return array ['backorders' => [], 'available' => []]
     */
    public function processOrderForBackorders(SalesOrder $order, ?int $warehouseId = null): array
    {
        $order->load('items.product');

        return DB::transaction(function () use ($order, $warehouseId) {
            $backorders = [];
            $available = [];

            foreach ($order->items as $item) {
                $availableStock = $this->getAvailableStock($item->product_id, $warehouseId);
                $remainingToShip = $item->remaining_quantity;

                if ($remainingToShip <= 0) {
                    continue; // Already fully shipped
                }

                if ($availableStock >= $remainingToShip) {
                    // Fully available
                    $available[] = [
                        'item' => $item,
                        'quantity' => $remainingToShip,
                    ];
                } elseif ($availableStock > 0) {
                    // Partially available
                    $available[] = [
                        'item' => $item,
                        'quantity' => $availableStock,
                    ];
                    $backorderQty = $remainingToShip - $availableStock;
                    $backorder = $this->createBackorder($item, $backorderQty, $warehouseId);
                    $backorders[] = $backorder;
                } else {
                    // Nothing available - full backorder
                    $backorder = $this->createBackorder($item, $remainingToShip, $warehouseId);
                    $backorders[] = $backorder;
                }
            }

            return [
                'backorders' => $backorders,
                'available' => $available,
            ];
        });
    }

    /**
     * Fulfill pending backorders when stock becomes available.
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @param float|null $maxQuantity Maximum quantity to fulfill (null = no limit)
     * @return array Fulfilled backorders with quantities
     */
    public function fulfillBackordersForProduct(
        int $productId,
        ?int $warehouseId = null,
        ?float $maxQuantity = null
    ): array {
        $availableStock = $this->getAvailableStock($productId, $warehouseId);

        if ($availableStock <= 0) {
            return [];
        }

        if ($maxQuantity !== null) {
            $availableStock = min($availableStock, $maxQuantity);
        }

        // Get pending backorders ordered by priority and date
        $query = Backorder::pending()
            ->forProduct($productId)
            ->byPriority()
            ->orderBy('created_at');

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhereNull('warehouse_id');
            });
        }

        $backorders = $query->get();
        $fulfilled = [];
        $remainingStock = $availableStock;

        return DB::transaction(function () use ($backorders, &$remainingStock, &$fulfilled) {
            foreach ($backorders as $backorder) {
                if ($remainingStock <= 0) {
                    break;
                }

                $toFulfill = min($backorder->remaining_quantity, $remainingStock);

                if ($toFulfill > 0) {
                    $backorder->fulfilled_quantity += $toFulfill;
                    $backorder->updateStatus();
                    $remainingStock -= $toFulfill;

                    $fulfilled[] = [
                        'backorder' => $backorder,
                        'fulfilled_quantity' => $toFulfill,
                        'remaining' => $backorder->remaining_quantity,
                    ];

                    // Update metadata with fulfillment history
                    $history = $backorder->metadata['fulfillment_history'] ?? [];
                    $history[] = [
                        'quantity' => $toFulfill,
                        'fulfilled_at' => now()->toIso8601String(),
                    ];
                    $backorder->update([
                        'metadata' => array_merge($backorder->metadata ?? [], [
                            'fulfillment_history' => $history,
                        ]),
                    ]);
                }
            }

            return $fulfilled;
        });
    }

    /**
     * Cancel a backorder.
     *
     * @param Backorder $backorder
     * @param string|null $reason
     * @return Backorder
     */
    public function cancelBackorder(Backorder $backorder, ?string $reason = null): Backorder
    {
        if (!$backorder->canBeCancelled()) {
            throw new InvalidArgumentException(
                "Backorder {$backorder->backorder_number} cannot be cancelled. Current status: {$backorder->status}"
            );
        }

        $backorder->update([
            'status' => 'cancelled',
            'metadata' => array_merge($backorder->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancellation_reason' => $reason,
            ]),
        ]);

        return $backorder->fresh();
    }

    /**
     * Manually fulfill a backorder with a specific quantity.
     *
     * @param Backorder $backorder
     * @param float $quantity
     * @param bool $createShipment Whether to create a shipment for fulfilled quantity
     * @return Backorder
     */
    public function fulfillBackorder(
        Backorder $backorder,
        float $quantity,
        bool $createShipment = false
    ): Backorder {
        if (!$backorder->isPending()) {
            throw new InvalidArgumentException(
                "Backorder {$backorder->backorder_number} is not pending fulfillment."
            );
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Fulfill quantity must be greater than 0.');
        }

        if ($quantity > $backorder->remaining_quantity) {
            throw new InvalidArgumentException(
                "Cannot fulfill {$quantity}. Only {$backorder->remaining_quantity} remaining."
            );
        }

        return DB::transaction(function () use ($backorder, $quantity, $createShipment) {
            $backorder->fulfilled_quantity += $quantity;
            $backorder->updateStatus();

            // Update fulfillment history
            $history = $backorder->metadata['fulfillment_history'] ?? [];
            $history[] = [
                'quantity' => $quantity,
                'fulfilled_at' => now()->toIso8601String(),
                'manual' => true,
            ];
            $backorder->update([
                'metadata' => array_merge($backorder->metadata ?? [], [
                    'fulfillment_history' => $history,
                ]),
            ]);

            // Optionally create shipment
            if ($createShipment) {
                $order = $backorder->salesOrder;
                $this->shipmentService->createShipment($order, [
                    $backorder->sales_order_item_id => $quantity,
                ], [
                    'warehouse_id' => $backorder->warehouse_id,
                    'notes' => "Backorder fulfillment: {$backorder->backorder_number}",
                ]);
            }

            return $backorder->fresh();
        });
    }

    /**
     * Get backorder summary for an order.
     */
    public function getOrderBackorderSummary(SalesOrder $order): array
    {
        $order->load(['backorders.product']);

        $backorders = $order->backorders ?? collect();

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_backorders' => $backorders->count(),
            'pending_backorders' => $backorders->where('status', 'pending')->count(),
            'partially_fulfilled' => $backorders->where('status', 'partially_fulfilled')->count(),
            'fulfilled_backorders' => $backorders->where('status', 'fulfilled')->count(),
            'cancelled_backorders' => $backorders->where('status', 'cancelled')->count(),
            'total_backorder_quantity' => $backorders->sum('backorder_quantity'),
            'total_fulfilled_quantity' => $backorders->sum('fulfilled_quantity'),
            'total_remaining_quantity' => $backorders->sum(fn($b) => $b->remaining_quantity),
            'backorders' => $backorders->map(fn($b) => [
                'id' => $b->id,
                'backorder_number' => $b->backorder_number,
                'product_id' => $b->product_id,
                'product_name' => $b->product->name ?? null,
                'backorder_quantity' => $b->backorder_quantity,
                'fulfilled_quantity' => $b->fulfilled_quantity,
                'remaining_quantity' => $b->remaining_quantity,
                'status' => $b->status,
                'priority' => $b->priority,
                'expected_date' => $b->expected_date?->toDateString(),
            ]),
        ];
    }

    /**
     * Get pending backorders for a product.
     */
    public function getPendingBackordersForProduct(int $productId): array
    {
        return Backorder::pending()
            ->forProduct($productId)
            ->byPriority()
            ->with(['salesOrder', 'salesOrderItem'])
            ->get()
            ->map(fn($b) => [
                'backorder_id' => $b->id,
                'backorder_number' => $b->backorder_number,
                'order_number' => $b->salesOrder->order_number,
                'remaining_quantity' => $b->remaining_quantity,
                'priority' => $b->priority,
                'expected_date' => $b->expected_date?->toDateString(),
                'created_at' => $b->created_at->toDateString(),
            ])
            ->toArray();
    }

    /**
     * Get available stock for a product.
     */
    protected function getAvailableStock(int $productId, ?int $warehouseId = null): float
    {
        $query = Stock::where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum(DB::raw('quantity - reserved_quantity'));
    }
}
