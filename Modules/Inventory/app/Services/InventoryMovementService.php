<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\InventoryMovement;
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

            // Update stock
            $stock->increment('quantity', $data['quantity']);
            $stock->update([
                'unit_cost' => $data['unit_cost'] ?? $stock->unit_cost,
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'entry',
            ]);

            $stock->refresh();
            $newStock = $stock->quantity;

            // Create movement record (total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_ENTRY,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_MANUAL,
                'reference_id' => $data['reference_id'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? 'Inventory entry',
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
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
            // Lock stock record to prevent race conditions
            $stock = Stock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->when(isset($data['warehouse_location_id']), function ($query) use ($data) {
                    $query->where('warehouse_location_id', $data['warehouse_location_id']);
                })
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new \Exception("Stock record not found for product {$data['product_id']} in warehouse {$data['warehouse_id']}");
            }

            // Validate sufficient quantity
            if ($stock->quantity < $data['quantity']) {
                throw new \Exception("Insufficient stock. Available: {$stock->quantity}, Requested: {$data['quantity']}");
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

            // Create movement record (total_value is auto-calculated by DB)
            $movement = InventoryMovement::create([
                'movement_type' => InventoryMovement::MOVEMENT_TYPE_EXIT,
                'reference_type' => $data['reference_type'] ?? InventoryMovement::REFERENCE_TYPE_MANUAL,
                'reference_id' => $data['reference_id'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'description' => $data['description'] ?? 'Inventory exit',
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_location_id' => $data['warehouse_location_id'] ?? null,
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
            throw new \Exception('Destination warehouse is required for transfers');
        }

        if ($data['warehouse_id'] === $data['destination_warehouse_id']) {
            throw new \Exception('Source and destination warehouses must be different');
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
                throw new \Exception("Stock record not found for product {$data['product_id']} in source warehouse {$data['warehouse_id']}");
            }

            // Validate sufficient quantity in source
            if ($sourceStock->quantity < $data['quantity']) {
                throw new \Exception("Insufficient stock in source warehouse. Available: {$sourceStock->quantity}, Requested: {$data['quantity']}");
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
                throw new \Exception("Stock record not found for product {$data['product_id']} in warehouse {$data['warehouse_id']}");
            }

            // Capture previous stock for audit trail
            $previousStock = $stock->quantity;

            // Adjustment can be positive or negative
            $adjustmentQuantity = $data['quantity']; // Can be negative

            // Update stock (can increase or decrease)
            $stock->quantity += $adjustmentQuantity;

            if ($stock->quantity < 0) {
                throw new \Exception("Adjustment would result in negative stock. Current: {$previousStock}, Adjustment: {$adjustmentQuantity}");
            }

            $stock->update([
                'last_movement_date' => $data['movement_date'] ?? now(),
                'last_movement_type' => 'adjustment',
            ]);

            $stock->refresh();
            $newStock = $stock->quantity;

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
            default => throw new \Exception("Invalid movement type: {$movementType}"),
        };
    }
}
