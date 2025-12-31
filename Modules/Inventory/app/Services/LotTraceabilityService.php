<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * LotTraceabilityService
 *
 * IV-M003: Lot Traceability Implementation
 *
 * Provides lot/batch traceability features:
 * - Forward/backward genealogy tracking
 * - FEFO (First Expire, First Out) batch selection
 * - Batch lifecycle management
 * - Expiration monitoring
 */
class LotTraceabilityService
{
    /**
     * Get complete movement history for a batch (genealogy).
     *
     * IV-M003: Trace all movements that affected this batch, both as source and destination.
     *
     * @param ProductBatch $batch
     * @return array
     */
    public function getBatchGenealogy(ProductBatch $batch): array
    {
        $movements = InventoryMovement::where('product_batch_id', $batch->id)
            ->orWhere('destination_batch_id', $batch->id)
            ->with(['product', 'warehouse', 'destinationWarehouse', 'user', 'productBatch', 'destinationBatch'])
            ->orderBy('movement_date', 'asc')
            ->get();

        $timeline = $movements->map(function ($movement) use ($batch) {
            $direction = $movement->product_batch_id === $batch->id ? 'outbound' : 'inbound';
            $quantityChange = $direction === 'outbound' ? -$movement->quantity : $movement->quantity;

            return [
                'movement_id' => $movement->id,
                'date' => $movement->movement_date->format('Y-m-d H:i:s'),
                'type' => $movement->movement_type,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'direction' => $direction,
                'quantity' => $movement->quantity,
                'quantity_change' => $quantityChange,
                'warehouse' => $movement->warehouse->name ?? null,
                'destination_warehouse' => $movement->destinationWarehouse->name ?? null,
                'user' => $movement->user->name ?? null,
                'status' => $movement->status,
                'description' => $movement->description,
            ];
        });

        return [
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'lot_number' => $batch->lot_number,
            'product' => [
                'id' => $batch->product_id,
                'name' => $batch->product->name ?? null,
                'sku' => $batch->product->sku ?? null,
            ],
            'supplier' => [
                'name' => $batch->supplier_name,
                'batch' => $batch->supplier_batch,
            ],
            'dates' => [
                'manufacturing' => $batch->manufacturing_date?->format('Y-m-d'),
                'expiration' => $batch->expiration_date?->format('Y-m-d'),
                'best_before' => $batch->best_before_date?->format('Y-m-d'),
                'created' => $batch->created_at?->format('Y-m-d H:i:s'),
            ],
            'quantities' => [
                'initial' => $batch->initial_quantity,
                'current' => $batch->current_quantity,
                'reserved' => $batch->reserved_quantity,
                'available' => $batch->current_quantity - $batch->reserved_quantity,
            ],
            'status' => $batch->status,
            'quality_status' => $batch->quality_status,
            'movement_count' => $movements->count(),
            'timeline' => $timeline->toArray(),
        ];
    }

    /**
     * Trace batch backward to find origin (where did it come from?).
     *
     * IV-M003: Find the original entry movement(s) that created this batch's inventory.
     *
     * @param ProductBatch $batch
     * @return array
     */
    public function traceBackward(ProductBatch $batch): array
    {
        // Find entry movements that added to this batch
        $entryMovements = InventoryMovement::where('product_batch_id', $batch->id)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_ENTRY)
            ->with(['user'])
            ->orderBy('movement_date', 'asc')
            ->get();

        // Find transfer movements where this batch was the destination
        $transfersIn = InventoryMovement::where('destination_batch_id', $batch->id)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_TRANSFER)
            ->with(['productBatch', 'user'])
            ->orderBy('movement_date', 'asc')
            ->get();

        return [
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'origin' => [
                'type' => $entryMovements->isNotEmpty() ? 'direct_receipt' : 'transfer',
                'supplier' => [
                    'name' => $batch->supplier_name,
                    'batch' => $batch->supplier_batch,
                ],
                'entry_movements' => $entryMovements->map(fn($m) => [
                    'id' => $m->id,
                    'date' => $m->movement_date->format('Y-m-d H:i:s'),
                    'quantity' => $m->quantity,
                    'reference_type' => $m->reference_type,
                    'reference_id' => $m->reference_id,
                ]),
                'source_batches' => $transfersIn->map(fn($m) => [
                    'batch_id' => $m->product_batch_id,
                    'batch_number' => $m->productBatch->batch_number ?? null,
                    'quantity' => $m->quantity,
                    'transfer_date' => $m->movement_date->format('Y-m-d H:i:s'),
                ]),
            ],
        ];
    }

    /**
     * Trace batch forward to find where it went (what used this batch?).
     *
     * IV-M003: Find all exit/transfer movements that consumed from this batch.
     *
     * @param ProductBatch $batch
     * @return array
     */
    public function traceForward(ProductBatch $batch): array
    {
        // Find exit movements from this batch
        $exitMovements = InventoryMovement::where('product_batch_id', $batch->id)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_EXIT)
            ->with(['user'])
            ->orderBy('movement_date', 'asc')
            ->get();

        // Find transfer movements where this batch was the source
        $transfersOut = InventoryMovement::where('product_batch_id', $batch->id)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_TRANSFER)
            ->with(['destinationBatch', 'destinationWarehouse', 'user'])
            ->orderBy('movement_date', 'asc')
            ->get();

        return [
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'consumption' => [
                'total_consumed' => $batch->initial_quantity - $batch->current_quantity,
                'exit_movements' => $exitMovements->map(fn($m) => [
                    'id' => $m->id,
                    'date' => $m->movement_date->format('Y-m-d H:i:s'),
                    'quantity' => $m->quantity,
                    'reference_type' => $m->reference_type,
                    'reference_id' => $m->reference_id,
                    'description' => $m->description,
                ]),
                'destination_batches' => $transfersOut->map(fn($m) => [
                    'batch_id' => $m->destination_batch_id,
                    'batch_number' => $m->destinationBatch->batch_number ?? null,
                    'warehouse' => $m->destinationWarehouse->name ?? null,
                    'quantity' => $m->quantity,
                    'transfer_date' => $m->movement_date->format('Y-m-d H:i:s'),
                ]),
            ],
        ];
    }

    /**
     * Select batches using FEFO (First Expire, First Out) algorithm.
     *
     * IV-M003: Automatically select batches to fulfill a quantity requirement,
     * prioritizing batches that expire soonest.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $requiredQuantity
     * @param int|null $locationId Optional specific location
     * @return array Selected batches with quantities to use from each
     */
    public function selectBatchesFEFO(
        int $productId,
        int $warehouseId,
        float $requiredQuantity,
        ?int $locationId = null
    ): array {
        $query = ProductBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->whereRaw('current_quantity - reserved_quantity > 0');

        if ($locationId) {
            $query->where('warehouse_location_id', $locationId);
        }

        // Order by FEFO: earliest expiration first, then by creation date
        $batches = $query->orderByRaw('COALESCE(expiration_date, "9999-12-31") ASC')
            ->orderBy('created_at', 'asc')
            ->get();

        $selectedBatches = [];
        $remainingQuantity = $requiredQuantity;
        $totalAvailable = 0;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableInBatch = $batch->current_quantity - $batch->reserved_quantity;
            $totalAvailable += $availableInBatch;
            $quantityFromBatch = min($remainingQuantity, $availableInBatch);

            $selectedBatches[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'lot_number' => $batch->lot_number,
                'expiration_date' => $batch->expiration_date?->format('Y-m-d'),
                'available_quantity' => $availableInBatch,
                'quantity_to_use' => $quantityFromBatch,
                'is_expiring_soon' => $batch->isExpiringSoon(),
            ];

            $remainingQuantity -= $quantityFromBatch;
        }

        $fulfilled = $remainingQuantity <= 0;

        return [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'required_quantity' => $requiredQuantity,
            'total_available' => $totalAvailable,
            'fulfilled' => $fulfilled,
            'shortage' => $fulfilled ? 0 : $remainingQuantity,
            'selected_batches' => $selectedBatches,
        ];
    }

    /**
     * Get batches expiring soon for a product or warehouse.
     *
     * IV-M003: Monitor expiration for proactive inventory management.
     *
     * @param int|null $productId Filter by product
     * @param int|null $warehouseId Filter by warehouse
     * @param int $daysThreshold Days until expiration (default 30)
     * @return Collection
     */
    public function getExpiringSoonBatches(
        ?int $productId = null,
        ?int $warehouseId = null,
        int $daysThreshold = 30
    ): Collection {
        $query = ProductBatch::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($daysThreshold))
            ->where('expiration_date', '>', now())
            ->where('current_quantity', '>', 0);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->with(['product', 'warehouse'])
            ->orderBy('expiration_date', 'asc')
            ->get()
            ->map(function ($batch) {
                $daysUntilExpiry = now()->diffInDays($batch->expiration_date, false);
                return [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'product' => [
                        'id' => $batch->product_id,
                        'name' => $batch->product->name ?? null,
                        'sku' => $batch->product->sku ?? null,
                    ],
                    'warehouse' => [
                        'id' => $batch->warehouse_id,
                        'name' => $batch->warehouse->name ?? null,
                    ],
                    'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                    'days_until_expiry' => $daysUntilExpiry,
                    'current_quantity' => $batch->current_quantity,
                    'available_quantity' => $batch->current_quantity - $batch->reserved_quantity,
                    'urgency' => $daysUntilExpiry <= 7 ? 'critical' : ($daysUntilExpiry <= 14 ? 'high' : 'medium'),
                ];
            });
    }

    /**
     * Get already expired batches that need attention.
     *
     * IV-M003: Identify expired inventory for quarantine/disposal.
     *
     * @param int|null $warehouseId Filter by warehouse
     * @return Collection
     */
    public function getExpiredBatches(?int $warehouseId = null): Collection
    {
        $query = ProductBatch::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now())
            ->where('current_quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->with(['product', 'warehouse'])
            ->orderBy('expiration_date', 'asc')
            ->get()
            ->map(function ($batch) {
                $daysExpired = now()->diffInDays($batch->expiration_date);
                return [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'product' => [
                        'id' => $batch->product_id,
                        'name' => $batch->product->name ?? null,
                        'sku' => $batch->product->sku ?? null,
                    ],
                    'warehouse' => [
                        'id' => $batch->warehouse_id,
                        'name' => $batch->warehouse->name ?? null,
                    ],
                    'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                    'days_expired' => $daysExpired,
                    'current_quantity' => $batch->current_quantity,
                    'total_value' => $batch->current_quantity * $batch->unit_cost,
                    'recommended_action' => $daysExpired > 30 ? 'dispose' : 'quarantine',
                ];
            });
    }

    /**
     * Search batches by lot number or batch number.
     *
     * IV-M003: Find specific batches for recall or quality investigation.
     *
     * @param string $searchTerm
     * @return Collection
     */
    public function searchBatches(string $searchTerm): Collection
    {
        return ProductBatch::where('batch_number', 'like', "%{$searchTerm}%")
            ->orWhere('lot_number', 'like', "%{$searchTerm}%")
            ->orWhere('supplier_batch', 'like', "%{$searchTerm}%")
            ->with(['product', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Get batch impact analysis for potential recall.
     *
     * IV-M003: Analyze the downstream impact of a potentially defective batch.
     *
     * @param ProductBatch $batch
     * @return array
     */
    public function getRecallImpactAnalysis(ProductBatch $batch): array
    {
        $forwardTrace = $this->traceForward($batch);

        // Count affected sales/transactions
        $exitMovements = InventoryMovement::where('product_batch_id', $batch->id)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_EXIT)
            ->get();

        $salesReferences = $exitMovements->where('reference_type', 'sale')
            ->pluck('reference_id')
            ->unique();

        return [
            'batch_info' => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'lot_number' => $batch->lot_number,
                'product' => $batch->product->name ?? null,
                'supplier' => $batch->supplier_name,
                'supplier_batch' => $batch->supplier_batch,
            ],
            'impact' => [
                'total_quantity_distributed' => $batch->initial_quantity - $batch->current_quantity,
                'remaining_quantity' => $batch->current_quantity,
                'affected_sales_count' => $salesReferences->count(),
                'affected_sales_ids' => $salesReferences->toArray(),
                'destination_batches' => count($forwardTrace['consumption']['destination_batches']),
            ],
            'recommended_actions' => [
                'quarantine_remaining' => $batch->current_quantity > 0,
                'notify_affected_customers' => $salesReferences->isNotEmpty(),
                'investigate_destination_batches' => !empty($forwardTrace['consumption']['destination_batches']),
            ],
        ];
    }

    /**
     * Mark batch for recall/quarantine.
     *
     * IV-M003: Update batch status and log the recall action.
     *
     * @param ProductBatch $batch
     * @param string $reason
     * @param int|null $userId
     * @return bool
     */
    public function initiateRecall(ProductBatch $batch, string $reason, ?int $userId = null): bool
    {
        $previousStatus = $batch->status;

        $batch->update([
            'status' => 'recalled',
            'quality_status' => 'quarantine',
            'quality_notes' => "RECALL: {$reason}. Previous status: {$previousStatus}",
            'metadata' => array_merge($batch->metadata ?? [], [
                'recall_initiated_at' => now()->toIso8601String(),
                'recall_initiated_by' => $userId,
                'recall_reason' => $reason,
            ]),
        ]);

        Log::warning('Batch recall initiated', [
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'lot_number' => $batch->lot_number,
            'product_id' => $batch->product_id,
            'reason' => $reason,
            'initiated_by' => $userId,
            'remaining_quantity' => $batch->current_quantity,
        ]);

        return true;
    }
}
