<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Exceptions\InventoryRuleException;
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
     * Inicia un conteo programado.
     */
    public function start(CycleCount $count): CycleCount
    {
        return DB::transaction(function () use ($count) {
            $count = CycleCount::whereKey($count->id)->lockForUpdate()->firstOrFail();
            if (!$count->isScheduled()) {
                throw new InventoryRuleException("Solo se inicia un conteo programado; {$count->count_number} está {$this->statusLabel($count)}.");
            }
            $count->update(['status' => CycleCount::STATUS_IN_PROGRESS]);

            return $count->fresh();
        });
    }

    /**
     * Registra lo contado en un conteo en proceso. La existencia en sistema se toma en
     * ese momento del registro de stock del producto en el almacen (y ubicacion, si el
     * conteo la tiene); la diferencia es contado menos sistema y se valora al costo del
     * stock. Con $adjust y diferencia distinta de cero se registra un ajuste en
     * Movimientos, asi la existencia queda igual a lo contado.
     */
    public function record(CycleCount $count, float $countedQuantity, int $userId, bool $adjust, ?string $notes = null): CycleCount
    {
        if ($countedQuantity < 0) {
            throw new InventoryRuleException('Lo contado no puede ser negativo.');
        }

        return DB::transaction(function () use ($count, $countedQuantity, $userId, $adjust, $notes) {
            $count = CycleCount::whereKey($count->id)->lockForUpdate()->firstOrFail();
            if (!$count->isInProgress()) {
                throw new InventoryRuleException("Solo se registra un conteo en proceso; {$count->count_number} está {$this->statusLabel($count)}. Inícialo primero.");
            }

            $stock = $this->stockFor($count);
            $systemQty = $stock ? (float) $stock->quantity : 0.0;
            $variance = round($countedQuantity - $systemQty, 4);
            $unitCost = (float) ($stock?->unit_cost ?: ($count->product->cost ?? 0));

            $movementId = null;
            if ($adjust && abs($variance) > 0.0001) {
                if (!$stock) {
                    throw new InventoryRuleException('Este producto no tiene registro de stock en el almacén: regístralo en Control de Stock con una entrada en lugar de ajustar.');
                }
                $movement = app(InventoryMovementService::class)->createAdjustment([
                    'product_id' => $count->product_id,
                    'warehouse_id' => $count->warehouse_id,
                    'warehouse_location_id' => $stock->warehouse_location_id,
                    'quantity' => $variance,
                    'movement_date' => now(),
                    'reference_type' => InventoryMovement::REFERENCE_TYPE_ADJUSTMENT,
                    'reference_id' => $count->id,
                    'description' => "Ajuste por conteo {$count->count_number}: sistema " . $this->qty($systemQty) . ', contado ' . $this->qty($countedQuantity),
                    'user_id' => $userId,
                    'metadata' => [
                        'cycle_count_id' => $count->id,
                        'system_quantity' => $systemQty,
                        'counted_quantity' => $countedQuantity,
                    ],
                ]);
                $movementId = $movement->id;
            }

            $count->update([
                'system_quantity' => $systemQty,
                'counted_quantity' => $countedQuantity,
                'variance_quantity' => $variance,
                'variance_value' => round($variance * $unitCost, 2),
                'counted_by' => $userId,
                'completed_date' => today(),
                'status' => CycleCount::STATUS_COMPLETED,
                'adjustment_movement_id' => $movementId,
                'notes' => $notes !== null && trim($notes) !== '' ? trim(($count->notes ? $count->notes . "\n" : '') . $notes) : $count->notes,
            ]);

            Log::info('Cycle count recorded', [
                'count_id' => $count->id,
                'variance' => $variance,
                'adjustment_movement_id' => $movementId,
            ]);

            return $count->fresh();
        });
    }

    /**
     * Cancela un conteo programado o en proceso.
     */
    public function cancel(CycleCount $count, ?string $reason = null): CycleCount
    {
        return DB::transaction(function () use ($count, $reason) {
            $count = CycleCount::whereKey($count->id)->lockForUpdate()->firstOrFail();
            if ($count->isCompleted() || $count->isCancelled()) {
                throw new InventoryRuleException("No se cancela un conteo {$this->statusLabel($count)} ({$count->count_number}).");
            }
            $reason = $reason !== null ? trim($reason) : '';
            $count->update([
                'status' => CycleCount::STATUS_CANCELLED,
                'notes' => $reason !== '' ? trim(($count->notes ? $count->notes . "\n" : '') . "Cancelado: {$reason}") : $count->notes,
            ]);

            return $count->fresh();
        });
    }

    /** Registro de stock del conteo: el de su ubicacion; sin ubicacion, el que no tiene, o el primero. */
    private function stockFor(CycleCount $count): ?Stock
    {
        $base = Stock::where('warehouse_id', $count->warehouse_id)->where('product_id', $count->product_id);
        if ($count->warehouse_location_id) {
            return (clone $base)->where('warehouse_location_id', $count->warehouse_location_id)->lockForUpdate()->first();
        }

        return (clone $base)->whereNull('warehouse_location_id')->lockForUpdate()->first()
            ?? (clone $base)->orderBy('id')->lockForUpdate()->first();
    }

    private function statusLabel(CycleCount $count): string
    {
        return match ($count->status) {
            CycleCount::STATUS_SCHEDULED => 'programado',
            CycleCount::STATUS_IN_PROGRESS => 'en proceso',
            CycleCount::STATUS_COMPLETED => 'completado',
            CycleCount::STATUS_CANCELLED => 'cancelado',
            default => (string) $count->status,
        };
    }

    private function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
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
