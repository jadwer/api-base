<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Inventory\Services\InventoryMovementService;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * INV-03 (22-11): una entrada sin ubicacion sumaba al primer registro de existencias del
 * producto en el almacen, aunque ese registro fuera de una ubicacion concreta.
 */
class EntryWithoutLocationTest extends TestCase
{
    public function test_entry_without_location_does_not_land_on_a_located_stock_record(): void
    {
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create();
        $service = app(InventoryMovementService::class);
        $base = [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_cost' => 10,
            'reference_type' => 'adjustment',
            'reference_id' => 1,
            'movement_date' => now(),
            'user_id' => $this->getAdminUser()->id,
        ];

        $service->createEntry($base + ['quantity' => 5, 'warehouse_location_id' => $location->id, 'description' => 'Con ubicacion']);
        $service->createEntry($base + ['quantity' => 3, 'description' => 'Sin ubicacion']);

        $located = Stock::where('product_id', $product->id)->where('warehouse_location_id', $location->id)->firstOrFail();
        $loose = Stock::where('product_id', $product->id)->whereNull('warehouse_location_id')->firstOrFail();
        $this->assertEqualsWithDelta(5, (float) $located->quantity, 0.001);
        $this->assertEqualsWithDelta(3, (float) $loose->quantity, 0.001);

        // Una salida sin ubicacion toma el registro con mas existencia.
        $service->createExit(['reference_type' => 'sales_order'] + $base + ['quantity' => 4, 'description' => 'Salida sin ubicacion']);
        $this->assertEqualsWithDelta(1, (float) $located->fresh()->quantity, 0.001);
        $this->assertEqualsWithDelta(3, (float) $loose->fresh()->quantity, 0.001);
    }
}
