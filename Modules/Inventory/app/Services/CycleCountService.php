<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * IV-M001: Cycle Count Scheduling Service
 *
 * Manages cycle count scheduling based on ABC analysis.
 *
 * ABC Classification Frequency:
 * - A items (high value/turnover): Monthly
 * - B items (medium value/turnover): Quarterly
 * - C items (low value/turnover): Annually
 */
class CycleCountService
{
    /**
     * Scheduling frequency in days for each ABC class
     */
    private const FREQUENCY_DAYS = [
        'A' => 30,   // Monthly
        'B' => 90,   // Quarterly
        'C' => 365,  // Annually
    ];

    /**
     * Generate count number
     */
    public function generateCountNumber(): string
    {
        $lastCount = CycleCount::orderBy('id', 'desc')->first();
        $nextNumber = $lastCount ? ((int) substr($lastCount->count_number, 3)) + 1 : 1;
        return 'CC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Schedule a single cycle count
     */
    public function scheduleCount(array $data): CycleCount
    {
        $data['count_number'] = $this->generateCountNumber();

        return CycleCount::create($data);
    }

    /**
     * Generate cycle counts for a warehouse based on ABC analysis
     *
     * @param Warehouse $warehouse
     * @param Carbon|null $forDate Date to schedule counts for (default: today)
     * @return Collection Collection of created CycleCount records
     */
    public function generateScheduleForWarehouse(Warehouse $warehouse, ?Carbon $forDate = null): Collection
    {
        $forDate = $forDate ?? now();
        $createdCounts = collect();

        // Get products that need counting based on ABC class
        $productsToCount = $this->getProductsDueForCount($warehouse->id, $forDate);

        foreach ($productsToCount as $productData) {
            $count = $this->scheduleCount([
                'warehouse_id' => $warehouse->id,
                'product_id' => $productData['product_id'],
                'scheduled_date' => $forDate,
                'abc_class' => $productData['abc_class'],
                'status' => CycleCount::STATUS_SCHEDULED,
            ]);

            $createdCounts->push($count);
        }

        Log::info('Cycle counts scheduled', [
            'warehouse_id' => $warehouse->id,
            'date' => $forDate->toDateString(),
            'count' => $createdCounts->count(),
        ]);

        return $createdCounts;
    }

    /**
     * Get products due for cycle count based on ABC classification
     *
     * @param int $warehouseId
     * @param Carbon $forDate
     * @return Collection
     */
    public function getProductsDueForCount(int $warehouseId, Carbon $forDate): Collection
    {
        $products = Stock::where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        return $products->filter(function ($stock) use ($warehouseId, $forDate) {
            $abcClass = $this->determineABCClass($stock->product);
            $lastCount = $this->getLastCountDate($warehouseId, $stock->product_id);

            if (!$lastCount) {
                return true; // Never counted
            }

            $daysSinceCount = $lastCount->diffInDays($forDate);
            $requiredFrequency = self::FREQUENCY_DAYS[$abcClass];

            return $daysSinceCount >= $requiredFrequency;
        })->map(function ($stock) {
            return [
                'product_id' => $stock->product_id,
                'abc_class' => $this->determineABCClass($stock->product),
            ];
        })->values();
    }

    /**
     * Determine ABC class for a product
     *
     * Classification based on:
     * - Unit cost (high cost = A)
     * - Movement frequency (if available)
     *
     * @param Product $product
     * @return string A, B, or C
     */
    public function determineABCClass(Product $product): string
    {
        // Simple classification based on price
        // In production, this should consider turnover, total value, etc.
        $price = $product->price ?? 0;

        if ($price >= 1000) {
            return CycleCount::ABC_CLASS_A;
        } elseif ($price >= 100) {
            return CycleCount::ABC_CLASS_B;
        } else {
            return CycleCount::ABC_CLASS_C;
        }
    }

    /**
     * Get last count date for a product in a warehouse
     */
    public function getLastCountDate(int $warehouseId, int $productId): ?Carbon
    {
        $lastCount = CycleCount::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('status', CycleCount::STATUS_COMPLETED)
            ->orderBy('completed_date', 'desc')
            ->first();

        return $lastCount?->completed_date;
    }

    /**
     * Complete a cycle count and optionally create adjustment
     *
     * @param CycleCount $count
     * @param float $countedQuantity
     * @param int|null $countedById
     * @param bool $createAdjustment Whether to create inventory adjustment for variance
     * @return CycleCount
     */
    public function completeCount(
        CycleCount $count,
        float $countedQuantity,
        ?int $countedById = null,
        bool $createAdjustment = false
    ): CycleCount {
        return DB::transaction(function () use ($count, $countedQuantity, $countedById, $createAdjustment) {
            // Record the count
            $count->recordCount($countedQuantity, $countedById);

            // Calculate variance value
            if ($count->has_variance && $count->product) {
                $unitCost = $count->product->cost ?? $count->product->price ?? 0;
                $count->update([
                    'variance_value' => $count->variance_quantity * $unitCost,
                ]);
            }

            // Create adjustment movement if requested and there's variance
            if ($createAdjustment && $count->has_variance) {
                $movement = $this->createAdjustmentMovement($count);
                $count->update(['adjustment_movement_id' => $movement->id]);
            }

            Log::info('Cycle count completed', [
                'count_id' => $count->id,
                'count_number' => $count->count_number,
                'variance' => $count->variance_quantity,
                'adjustment_created' => $createAdjustment && $count->has_variance,
            ]);

            return $count->fresh();
        });
    }

    /**
     * Create inventory adjustment movement for variance
     */
    private function createAdjustmentMovement(CycleCount $count): InventoryMovement
    {
        $movementService = app(InventoryMovementService::class);

        return $movementService->createMovement([
            'product_id' => $count->product_id,
            'warehouse_id' => $count->warehouse_id,
            'warehouse_location_id' => $count->warehouse_location_id,
            'movement_type' => 'adjustment',
            'quantity' => $count->variance_quantity,
            'movement_date' => now(),
            'reference' => $count->count_number,
            'notes' => "Cycle count adjustment - {$count->count_number}",
            'metadata' => [
                'cycle_count_id' => $count->id,
                'system_quantity' => $count->system_quantity,
                'counted_quantity' => $count->counted_quantity,
            ],
        ]);
    }

    /**
     * Get counts due today
     */
    public function getCountsDueToday(?int $warehouseId = null): Collection
    {
        $query = CycleCount::dueToday()->with(['warehouse', 'product', 'assignedTo']);

        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }

        return $query->get();
    }

    /**
     * Get overdue counts
     */
    public function getOverdueCounts(?int $warehouseId = null): Collection
    {
        $query = CycleCount::overdue()->with(['warehouse', 'product', 'assignedTo']);

        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }

        return $query->orderBy('scheduled_date')->get();
    }

    /**
     * Get count accuracy metrics for a warehouse
     */
    public function getAccuracyMetrics(int $warehouseId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = CycleCount::completed()
            ->forWarehouse($warehouseId);

        if ($startDate) {
            $query->where('completed_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('completed_date', '<=', $endDate);
        }

        $counts = $query->get();

        if ($counts->isEmpty()) {
            return [
                'total_counts' => 0,
                'counts_with_variance' => 0,
                'accuracy_rate' => 100,
                'total_variance_value' => 0,
                'average_variance_percentage' => 0,
            ];
        }

        $countsWithVariance = $counts->filter(fn ($c) => $c->has_variance);

        return [
            'total_counts' => $counts->count(),
            'counts_with_variance' => $countsWithVariance->count(),
            'accuracy_rate' => round((1 - ($countsWithVariance->count() / $counts->count())) * 100, 2),
            'total_variance_value' => $counts->sum('variance_value'),
            'average_variance_percentage' => $counts->avg('variance_percentage') ?? 0,
        ];
    }
}
