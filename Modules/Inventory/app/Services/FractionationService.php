<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Sales\Models\FolioSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FractionationService
{
    protected InventoryMovementService $movementService;

    public function __construct(InventoryMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    /**
     * Calculate a fractionation preview without touching the database.
     *
     * @param array $data [source_product_id, destination_product_id, source_quantity, warehouse_id]
     * @return array Preview data
     * @throws \Exception
     */
    public function calculate(array $data): array
    {
        $conversion = ProductConversion::active()
            ->where('source_product_id', $data['source_product_id'])
            ->where('destination_product_id', $data['destination_product_id'])
            ->with(['sourceProduct.unit', 'destinationProduct.unit'])
            ->first();

        if (!$conversion) {
            throw new \Exception(
                "No active conversion found for source product {$data['source_product_id']} "
                . "to destination product {$data['destination_product_id']}"
            );
        }

        $sourceQuantity = (float) $data['source_quantity'];
        $producedQuantity = $conversion->calculateProducedQuantity($sourceQuantity);
        $wasteQuantity = $conversion->calculateWasteQuantity($sourceQuantity);

        // Check available stock
        $stock = Stock::where('product_id', $data['source_product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->first();

        $availableStock = $stock ? (float) $stock->quantity : 0;

        return [
            'source_product' => [
                'id' => $conversion->sourceProduct->id,
                'name' => $conversion->sourceProduct->name,
                'sku' => $conversion->sourceProduct->sku,
                'unit' => $conversion->sourceProduct->unit ? [
                    'name' => $conversion->sourceProduct->unit->name,
                    'code' => $conversion->sourceProduct->unit->code,
                ] : null,
            ],
            'destination_product' => [
                'id' => $conversion->destinationProduct->id,
                'name' => $conversion->destinationProduct->name,
                'sku' => $conversion->destinationProduct->sku,
                'unit' => $conversion->destinationProduct->unit ? [
                    'name' => $conversion->destinationProduct->unit->name,
                    'code' => $conversion->destinationProduct->unit->code,
                ] : null,
            ],
            'source_quantity' => $sourceQuantity,
            'conversion_factor' => $conversion->conversion_factor,
            'waste_percentage' => $conversion->waste_percentage,
            'produced_quantity' => round($producedQuantity, 4),
            'waste_quantity' => round($wasteQuantity, 4),
            'available_stock' => $availableStock,
            'has_enough_stock' => $availableStock >= $sourceQuantity,
        ];
    }

    /**
     * Execute a fractionation operation atomically.
     *
     * Creates an exit movement for the source product and an entry movement
     * for the destination product, all within a single transaction.
     *
     * @param array $data [source_product_id, destination_product_id, source_quantity, warehouse_id, user_id, notes?]
     * @return Fractionation
     * @throws \Exception
     */
    public function execute(array $data): Fractionation
    {
        return DB::transaction(function () use ($data) {
            // 1. Find and validate conversion
            $conversion = ProductConversion::active()
                ->where('source_product_id', $data['source_product_id'])
                ->where('destination_product_id', $data['destination_product_id'])
                ->first();

            if (!$conversion) {
                throw new \Exception(
                    "No active conversion found for source product {$data['source_product_id']} "
                    . "to destination product {$data['destination_product_id']}"
                );
            }

            // 2. Lock source stock and validate availability
            $sourceStock = Stock::where('product_id', $data['source_product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$sourceStock) {
                throw new \Exception(
                    "No stock record found for source product {$data['source_product_id']} "
                    . "in warehouse {$data['warehouse_id']}"
                );
            }

            $sourceQuantity = (float) $data['source_quantity'];

            if ($sourceStock->quantity < $sourceQuantity) {
                throw new \Exception(
                    "Insufficient stock. Available: {$sourceStock->quantity}, Requested: {$sourceQuantity}"
                );
            }

            // 3. Calculate quantities
            $producedQuantity = round($conversion->calculateProducedQuantity($sourceQuantity), 4);
            $wasteQuantity = round($conversion->calculateWasteQuantity($sourceQuantity), 4);

            // 4. Generate folio
            $folioNumber = FolioSequence::getNextFolio('fractionation');

            // 5. Create exit movement for source product
            $exitMovement = $this->movementService->createExit([
                'product_id' => $data['source_product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $sourceQuantity,
                'unit_cost' => $sourceStock->unit_cost,
                'user_id' => $data['user_id'],
                'reference_type' => 'fractionation',
                'description' => "Fraccionamiento {$folioNumber}: salida de producto origen",
                'quality_checked' => true,
                'quality_checked_by' => $data['user_id'],
                'metadata' => [
                    'fractionation_folio' => $folioNumber,
                    'fractionation_type' => 'exit',
                ],
            ]);

            // 6. Create entry movement for destination product
            $entryMovement = $this->movementService->createEntry([
                'product_id' => $data['destination_product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $producedQuantity,
                'unit_cost' => 0, // Cost will be calculated from source
                'user_id' => $data['user_id'],
                'reference_type' => 'fractionation',
                'description' => "Fraccionamiento {$folioNumber}: entrada de producto destino",
                'metadata' => [
                    'fractionation_folio' => $folioNumber,
                    'fractionation_type' => 'entry',
                ],
            ]);

            // 7. Create fractionation record
            $fractionation = Fractionation::create([
                'folio_number' => $folioNumber,
                'source_product_id' => $data['source_product_id'],
                'destination_product_id' => $data['destination_product_id'],
                'product_conversion_id' => $conversion->id,
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $data['user_id'],
                'source_quantity' => $sourceQuantity,
                'produced_quantity' => $producedQuantity,
                'waste_percentage' => $conversion->waste_percentage,
                'waste_quantity' => $wasteQuantity,
                'conversion_factor_used' => $conversion->conversion_factor,
                'exit_movement_id' => $exitMovement->id,
                'entry_movement_id' => $entryMovement->id,
                'status' => Fractionation::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
                'executed_at' => now(),
            ]);

            Log::info('Fractionation executed', [
                'folio' => $folioNumber,
                'source_product_id' => $data['source_product_id'],
                'destination_product_id' => $data['destination_product_id'],
                'source_quantity' => $sourceQuantity,
                'produced_quantity' => $producedQuantity,
                'waste_quantity' => $wasteQuantity,
                'warehouse_id' => $data['warehouse_id'],
                'exit_movement_id' => $exitMovement->id,
                'entry_movement_id' => $entryMovement->id,
            ]);

            return $fractionation->load([
                'sourceProduct', 'destinationProduct', 'warehouse',
                'user', 'exitMovement', 'entryMovement',
            ]);
        });
    }
}
