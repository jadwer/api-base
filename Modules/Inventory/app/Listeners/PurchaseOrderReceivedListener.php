<?php

namespace Modules\Inventory\Listeners;

use Modules\Purchase\Events\PurchaseOrderReceived;
use Modules\Inventory\Services\InventoryMovementService;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Support\Facades\Log;

class PurchaseOrderReceivedListener
{
    /**
     * @var InventoryMovementService
     */
    protected InventoryMovementService $inventoryMovementService;

    /**
     * Create the event listener.
     */
    public function __construct(InventoryMovementService $inventoryMovementService)
    {
        $this->inventoryMovementService = $inventoryMovementService;
    }

    /**
     * Handle the event.
     * Create inventory entry movements for all items in the purchase order
     */
    public function handle(PurchaseOrderReceived $event): void
    {
        $purchaseOrder = $event->purchaseOrder;

        // Idempotency guard: skip if movements already exist for this PO
        $existingMovements = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->exists();

        if ($existingMovements) {
            Log::info('Inventory movements already exist for PurchaseOrder, skipping', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
            return;
        }

        // Skip if purchase order items not loaded
        if (!$purchaseOrder->relationLoaded('purchaseOrderItems')) {
            $purchaseOrder->load('purchaseOrderItems');
        }

        // Use warehouse from purchase order, or fallback to first active warehouse
        $warehouse = $purchaseOrder->warehouse_id
            ? Warehouse::find($purchaseOrder->warehouse_id)
            : Warehouse::where('is_active', true)->first();

        if (!$warehouse) {
            Log::warning('No active warehouse found for PurchaseOrder', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
            return;
        }

        // Create inventory entry movements for each item
        foreach ($purchaseOrder->purchaseOrderItems as $item) {
            try {
                $movement = $this->inventoryMovementService->createEntry([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse->id,
                    'warehouse_location_id' => null, // Can be enhanced to use specific location
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchaseOrder->id,
                    'movement_date' => now(),
                    'description' => "Received from Purchase Order #{$purchaseOrder->id}",
                    'user_id' => auth()->id() ?? \Modules\User\Models\User::first()?->id ?? 1, // System user if not authenticated
                    'metadata' => [
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_order_item_id' => $item->id,
                        'supplier_id' => $purchaseOrder->contact_id,
                    ],
                ]);

                Log::info('Inventory movement created from Purchase Order', [
                    'movement_id' => $movement->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create inventory movement from Purchase Order', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);

                // Re-throw exception to rollback the entire operation
                throw $e;
            }
        }
    }
}
