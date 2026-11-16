<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Exceptions\InventoryRuleException;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InventoryMovementService
{
    /**
     * Create an inventory entry movement with atomic transaction
     *
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function createEntry(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // R1: idempotencia por clave natural con UNIQUE en BD. Fast-path legible
            // aqui; la garantia real es el constraint (un insert duplicado concurrente
            // viola el unique y revierte, la BD nunca permite el doble movimiento).
            if (!empty($data['idempotency_key'])) {
                $existing = InventoryMovement::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            // Lock stock record to prevent race conditions
            $stock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->when(isset($data['warehouse_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['warehouse_location_id']);
                })
                ->lockForUpdate()
                ->first();

            // Create stock record if doesn't exist
            if (!$stock) {
                $stock = Stock::create([
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'minimum_stock' => 0,
                    'reorder_point' => 0,
                    'unit_cost' => $data['unit_cost'] ?? 0,
                    'status' => 'active',
                ]);
            }

            // Capture previous stock for audit trail
            $previousStock = $stock->quantity;

            // Costo del stock: por defecto el ultimo costo recibido (politica
            // general, sin cambio). 'stock_cost_policy' => 'weighted_average' lo
            // pide solo quien transforma inventario (fraccionamiento, P3 granel):
            // promedio ponderado con lo que ya habia y una entrada sin costo no
            // pisa el costo existente con 0.
            $stockUnitCost = ($data['stock_cost_policy'] ?? null) === 'weighted_average'
                ? self::weightedAverageCost(
                    (float) $previousStock,
                    (float) $stock->unit_cost,
                    (float) $data['quantity'],
                    (float) ($data['unit_cost'] ?? 0)
                )
                : ($data['unit_cost'] ?? $stock->unit_cost);

            // Update stock
            $stock->increment('quantity', $data['quantity']);
            $stock->update([
                'unit_cost' => $stockUnitCost,
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'entry',
            ]);

            $stock->refresh();
            $newStock = $stock->quantity;

            // Cuenta del lote (decision 2026-11-16): la entrada suma al lote
            $batch = $this->lockBatch($data);
            if ($batch) {
                $this->addToBatch($batch, (float) $data['quantity'], (float) ($data['unit_cost'] ?? 0));
            }

            // Create movement record (total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_ENTRY,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_MANUAL,
                'reference_id' => $data['reference_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? 'Inventory entry',
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                'product_batch_id' => $data['product_batch_id'] ?? null,
                'destination_batch_id' => $data['destination_batch_id'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'user_id' => $data['user_id'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'batch_info' => $data['batch_info'] ?? [],
                'metadata' => $data['metadata'] ?? [],
            ]);

            Log::info('Inventory entry created', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
            ]);

            return $movement;
        });
    }

    /**
     * Create an inventory exit movement with atomic transaction
     *
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function createExit(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // R1: idempotencia por clave natural con UNIQUE en BD (ver createEntry).
            if (!empty($data['idempotency_key'])) {
                $existing = InventoryMovement::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            // Lock stock record to prevent race conditions
            $stock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->when(isset($data['warehouse_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['warehouse_location_id']);
                })
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new InventoryRuleException("No hay existencia registrada del producto {$data['product_id']} en el almacén {$data['warehouse_id']}.");
            }

            // Validate sufficient quantity
            if ($stock->quantity < $data['quantity']) {
                throw new InventoryRuleException("Existencia insuficiente. Disponible: {$stock->quantity}, solicitado: {$data['quantity']}.");
            }

            // Capture previous stock for audit trail
            $previousStock = $stock->quantity;

            // Update stock
            $stock->decrement('quantity', $data['quantity']);
            $stock->update([
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'exit',
            ]);

            $stock->refresh();
            $newStock = $stock->quantity;

            // Cuenta del lote: la salida resta del lote y no deja sacar mas de lo que tiene
            $batch = $this->lockBatch($data);
            if ($batch) {
                $this->takeFromBatch($batch, (float) $data['quantity']);
            }

            // Create movement record (total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_EXIT,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_MANUAL,
                'reference_id' => $data['reference_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? 'Inventory exit',
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                'product_batch_id' => $data['product_batch_id'] ?? null,
                'destination_batch_id' => $data['destination_batch_id'] ?? null,
                'quantity' => $data['quantity'],
                // QA post-commit (A2): antes se forzaba $stock->unit_cost ignorando el
                // payload; con stock entrado sin costo (unit_cost=0) el COGS se posteaba
                // en $0. Prioridad: costo del stock si es real (>0), si no el costo que
                // aporte el caller (ej. costo del producto en la entrega por remision).
                'unit_cost' => ((float) $stock->unit_cost > 0)
                    ? $stock->unit_cost
                    : (float) ($data['unit_cost'] ?? 0),
                'status' => InventoryMovement::STATUS_COMPLETED,
                'user_id' => $data['user_id'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'batch_info' => $data['batch_info'] ?? [],
                'metadata' => $data['metadata'] ?? [],
                // Quality check fields (IV-009)
                'quality_checked' => $data['quality_checked'] ?? false,
                'quality_checked_at' => ($data['quality_checked'] ?? false) ? now() : null,
                'quality_checked_by' => $data['quality_checked_by'] ?? $data['user_id'],
                'quality_check_notes' => $data['quality_check_notes'] ?? null,
            ]);

            Log::info('Inventory exit created', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
            ]);

            return $movement;
        });
    }

    /**
     * Create an inventory transfer movement with atomic transaction
     * This is the CRITICAL P0 fix - ensures atomicity for warehouse transfers
     *
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function createTransfer(array $data): InventoryMovement
    {
        // Validate required fields for transfer
        if (!isset($data['destination_warehouse_id'])) {
            throw new InventoryRuleException('El almacén destino es obligatorio en las transferencias.');
        }

        if ($data['warehouse_id'] === $data['destination_warehouse_id']) {
            throw new InventoryRuleException('El almacén origen y el destino deben ser diferentes.');
        }

        return DB::transaction(function () use ($data) {
            // Lock BOTH source and destination stock records to prevent race conditions
            $sourceStock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->when(isset($data['warehouse_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['warehouse_location_id']);
                })
                ->lockForUpdate()
                ->first();

            if (!$sourceStock) {
                throw new InventoryRuleException("No hay existencia registrada del producto {$data['product_id']} en el almacén origen {$data['warehouse_id']}.");
            }

            // Validate sufficient quantity in source
            if ($sourceStock->quantity < $data['quantity']) {
                throw new InventoryRuleException("Existencia insuficiente en el almacén origen. Disponible: {$sourceStock->quantity}, solicitado: {$data['quantity']}.");
            }

            // Lock destination stock (or create if doesn't exist)
            $destinationStock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['destination_warehouse_id'])
                ->when(isset($data['destination_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['destination_location_id']);
                })
                ->lockForUpdate()
                ->first();

            if (!$destinationStock) {
                $destinationStock = Stock::create([
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['destination_warehouse_id'],
                    'warehouse_location_id' => $data['destination_location_id'] ?? null,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'minimum_stock' => 0,
                    'reorder_point' => 0,
                    'unit_cost' => $sourceStock->unit_cost,
                    'status' => 'active',
                ]);
            }

            // Capture previous stocks for audit trail
            $previousSourceStock = $sourceStock->quantity;
            $previousDestinationStock = $destinationStock->quantity;

            // Update source stock (decrease)
            $sourceStock->decrement('quantity', $data['quantity']);
            $sourceStock->update([
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'transfer_out',
            ]);

            // Update destination stock (increase)
            $destinationStock->increment('quantity', $data['quantity']);
            $destinationStock->update([
                'unit_cost' => $sourceStock->unit_cost, // Maintain same cost
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'transfer_in',
            ]);

            $sourceStock->refresh();
            $destinationStock->refresh();

            $newSourceStock = $sourceStock->quantity;
            $newDestinationStock = $destinationStock->quantity;

            // Cuenta del lote: sale del lote origen y entra al mismo lote en el almacen destino
            $batch = $this->lockBatch($data);
            $destinationBatch = null;
            if ($batch) {
                $this->takeFromBatch($batch, (float) $data['quantity']);
                $destinationBatch = $this->destinationBatchFor($batch, (int) $data['destination_warehouse_id'], $data['destination_location_id'] ?? null);
                $this->addToBatch($destinationBatch, (float) $data['quantity'], (float) $batch->unit_cost);
            }

            // Create movement record (represents the transfer operation, total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_TRANSFER,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_TRANSFER,
                'reference_id' => $data['reference_id'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? "Transfer from warehouse {$data['warehouse_id']} to {$data['destination_warehouse_id']}",
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'destination_location_id' => $data['destination_location_id'] ?? null,
                'product_batch_id' => $data['product_batch_id'] ?? null,
                'destination_batch_id' => $destinationBatch?->id ?? ($data['destination_batch_id'] ?? null),
                'quantity' => $data['quantity'],
                'unit_cost' => $sourceStock->unit_cost,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'user_id' => $data['user_id'],
                'previous_stock' => $previousSourceStock,
                'new_stock' => $newSourceStock,
                'batch_info' => $data['batch_info'] ?? [],
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'destination_previous_stock' => $previousDestinationStock,
                    'destination_new_stock' => $newDestinationStock,
                ]),
                // Quality check fields (IV-009)
                'quality_checked' => $data['quality_checked'] ?? false,
                'quality_checked_at' => ($data['quality_checked'] ?? false) ? now() : null,
                'quality_checked_by' => $data['quality_checked_by'] ?? $data['user_id'],
                'quality_check_notes' => $data['quality_check_notes'] ?? null,
            ]);

            Log::info('Inventory transfer created atomically', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'source_warehouse' => $data['warehouse_id'],
                'destination_warehouse' => $data['destination_warehouse_id'],
                'quantity' => $data['quantity'],
                'source_stock' => ['previous' => $previousSourceStock, 'new' => $newSourceStock],
                'destination_stock' => ['previous' => $previousDestinationStock, 'new' => $newDestinationStock],
            ]);

            return $movement;
        });
    }

    /**
     * Create an inventory adjustment movement with atomic transaction
     *
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function createAdjustment(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // Lock stock record to prevent race conditions
            $stock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->when(isset($data['warehouse_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['warehouse_location_id']);
                })
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new InventoryRuleException("No hay existencia registrada del producto {$data['product_id']} en el almacén {$data['warehouse_id']}.");
            }

            // Capture previous stock for audit trail
            $previousStock = $stock->quantity;

            // Adjustment can be positive or negative
            $adjustmentQuantity = $data['quantity']; // Can be negative

            // Update stock (can increase or decrease)
            $stock->quantity += $adjustmentQuantity;

            if ($stock->quantity < 0) {
                throw new InventoryRuleException("El ajuste dejaría la existencia en negativo. Actual: {$previousStock}, ajuste: {$adjustmentQuantity}.");
            }

            $stock->update([
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'adjustment',
            ]);

            $stock->refresh();
            $newStock = $stock->quantity;

            // Cuenta del lote: el ajuste suma o resta en el lote (un ajuste si puede tocar uno en cuarentena)
            $batch = $this->lockBatch($data);
            if ($batch) {
                $adjustmentQuantity < 0
                    ? $this->takeFromBatch($batch, abs((float) $adjustmentQuantity), true)
                    : $this->addToBatch($batch, (float) $adjustmentQuantity, (float) $batch->unit_cost);
            }

            // Create movement record (total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_ADJUSTMENT,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_ADJUSTMENT,
                'reference_id' => $data['reference_id'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? 'Inventory adjustment',
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
                'product_batch_id' => $data['product_batch_id'] ?? null,
                'quantity' => $adjustmentQuantity,
                'unit_cost' => $stock->unit_cost,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'user_id' => $data['user_id'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'batch_info' => $data['batch_info'] ?? [],
                'metadata' => $data['metadata'] ?? [],
            ]);

            Log::info('Inventory adjustment created', [
                'movement_id' => $movement->id,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'adjustment_quantity' => $adjustmentQuantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
            ]);

            return $movement;
        });
    }

    /**
     * Promedio ponderado del costo del stock tras una entrada.
     *
     * - Entrada sin costo (<= 0): conserva el costo existente.
     * - Sin existencia previa o sin costo previo: toma el costo de la entrada.
     * - Si no: (qty_prev * costo_prev + qty_in * costo_in) / (qty_prev + qty_in).
     */
    public static function weightedAverageCost(float $previousQty, float $previousCost, float $incomingQty, float $incomingCost): float
    {
        if ($incomingCost <= 0) {
            return $previousCost;
        }
        if ($previousQty <= 0 || $previousCost <= 0) {
            return $incomingCost;
        }
        $totalQty = $previousQty + $incomingQty;
        if ($totalQty <= 0) {
            return $incomingCost;
        }

        return round(($previousQty * $previousCost + $incomingQty * $incomingCost) / $totalQty, 4);
    }

    /**
     * Create any type of movement (routes to appropriate method)
     *
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function createMovement(array $data): InventoryMovement
    {
        $movementType = $data['movement_type'] ?? null;

        return match($movementType) {
            InventoryMovement::MOVEMENT_TYPE_ENTRY => $this->createEntry($data),
            InventoryMovement::MOVEMENT_TYPE_EXIT => $this->createExit($data),
            InventoryMovement::MOVEMENT_TYPE_TRANSFER => $this->createTransfer($data),
            InventoryMovement::MOVEMENT_TYPE_ADJUSTMENT => $this->createAdjustment($data),
            default => throw new InventoryRuleException("Tipo de movimiento no válido: {$movementType}."),
        };
    }

    /** Estatus del lote que no dejan sacar producto (salvo un ajuste). */
    private const BATCH_BLOCKED_FOR_EXIT = [
        'quarantine' => 'en cuarentena',
        'recalled' => 'retirado del mercado',
        'expired' => 'vencido',
    ];

    private const BATCH_EPSILON = 0.00005;

    /**
     * Lote del movimiento, bloqueado para la transaccion. Null si el movimiento no trae lote
     * o si el llamador lleva la cuenta del lote (fraccionamiento: batch_managed_by_caller).
     */
    private function lockBatch(array $data): ?ProductBatch
    {
        if (empty($data['product_batch_id']) || !empty($data['batch_managed_by_caller'])) {
            return null;
        }
        $batch = ProductBatch::whereKey((int) $data['product_batch_id'])->lockForUpdate()->first();
        if (!$batch) {
            throw new InventoryRuleException('El lote del movimiento no existe.');
        }
        if ((int) $batch->product_id !== (int) $data['product_id']) {
            throw new InventoryRuleException("El lote {$batch->batch_number} es de otro producto.");
        }
        if ((int) $batch->warehouse_id !== (int) $data['warehouse_id']) {
            throw new InventoryRuleException("El lote {$batch->batch_number} está en otro almacén.");
        }

        return $batch;
    }

    /** Suma al lote; la cantidad inicial sigue a lo que ha entrado y un lote agotado se reactiva. */
    private function addToBatch(ProductBatch $batch, float $quantity, float $unitCost): void
    {
        $batch->current_quantity = round((float) $batch->current_quantity + $quantity, 4);
        if ((float) $batch->current_quantity > (float) $batch->initial_quantity) {
            $batch->initial_quantity = $batch->current_quantity;
        }
        if ((float) $batch->unit_cost <= 0 && $unitCost > 0) {
            $batch->unit_cost = round($unitCost, 4);
        }
        if ($batch->status === 'consumed' && (float) $batch->current_quantity > 0) {
            $batch->status = 'active';
        }
        $batch->save();
    }

    /** Resta del lote: no deja sacar mas de lo disponible ni de un lote bloqueado. */
    private function takeFromBatch(ProductBatch $batch, float $quantity, bool $isAdjustment = false): void
    {
        if (!$isAdjustment && isset(self::BATCH_BLOCKED_FOR_EXIT[$batch->status])) {
            throw new InventoryRuleException("El lote {$batch->batch_number} está ".self::BATCH_BLOCKED_FOR_EXIT[$batch->status].'; no se puede sacar producto de él.');
        }
        $available = round((float) $batch->current_quantity - (float) $batch->reserved_quantity, 4);
        $limit = $isAdjustment ? (float) $batch->current_quantity : $available;
        if ($quantity > $limit + self::BATCH_EPSILON) {
            throw new InventoryRuleException("El lote {$batch->batch_number} tiene {$limit} disponible; se pidieron {$quantity}.");
        }
        $remaining = round((float) $batch->current_quantity - $quantity, 4);
        $batch->current_quantity = $remaining <= self::BATCH_EPSILON ? 0 : $remaining;
        if ((float) $batch->current_quantity <= 0 && $batch->status === 'active') {
            $batch->status = 'consumed';
        }
        $batch->save();
    }

    /**
     * Lote espejo en el almacen destino de una transferencia: el mismo lote del fabricante,
     * fechas y proveedor. batch_number es unico, asi que el espejo lleva sufijo y apunta a
     * su origen en metadata.source_batch_id (la genealogia lo sigue por destination_batch_id).
     */
    private function destinationBatchFor(ProductBatch $source, int $warehouseId, mixed $locationId): ProductBatch
    {
        $existing = ProductBatch::where('product_id', $source->product_id)
            ->where('warehouse_id', $warehouseId)
            ->when($locationId, fn ($q) => $q->where('warehouse_location_id', $locationId), fn ($q) => $q->whereNull('warehouse_location_id'))
            ->where('metadata->source_batch_id', $source->id)
            ->lockForUpdate()
            ->first();
        if ($existing) {
            return $existing;
        }

        $base = "{$source->batch_number}-T{$warehouseId}";
        $number = $base;
        $suffix = 1;
        while (ProductBatch::where('batch_number', $number)->exists()) {
            $suffix++;
            $number = "{$base}-{$suffix}";
        }

        return ProductBatch::create([
            'product_id' => $source->product_id,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $locationId ?: null,
            'batch_number' => $number,
            'lot_number' => $source->lot_number,
            'manufacturing_date' => $source->manufacturing_date,
            'expiration_date' => $source->expiration_date,
            'best_before_date' => $source->best_before_date,
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'reserved_quantity' => 0,
            'unit_cost' => $source->unit_cost,
            'status' => 'active',
            'quality_status' => $source->quality_status ?? 'pending',
            'supplier_name' => $source->supplier_name,
            'supplier_batch' => $source->supplier_batch,
            'certifications' => $source->certifications,
            'test_results' => $source->test_results,
            'metadata' => [
                'origin' => 'transfer',
                'source_batch_id' => $source->id,
                'source_batch_number' => $source->batch_number,
            ],
        ]);
    }
}
