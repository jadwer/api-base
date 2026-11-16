<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\WarehouseLocation;
use Tests\TestCase;

/**
 * En PATCH, laravel-json-api mezcla los valores guardados antes de llenar el modelo; un
 * Number sin acceptStrings() truena con los decimales guardados ("3.0000") aunque el
 * cliente no los mande (500 en dev, 2026-11-16).
 */
class PartialPatchDecimalsTest extends TestCase
{
    public function test_actualizar_solo_el_minimo_respeta_los_demas_niveles_guardados(): void
    {
        $stock = Stock::factory()->create(['minimum_stock' => 1, 'maximum_stock' => 9, 'reorder_point' => 3, 'status' => 'active']);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('stocks')
            ->withData(['type' => 'stocks', 'id' => (string) $stock->id, 'attributes' => ['minimumStock' => 2]])
            ->patch("/api/v1/stocks/{$stock->id}")
            ->assertOk();

        $this->assertDatabaseHas('stock', ['id' => $stock->id, 'minimum_stock' => 2, 'maximum_stock' => 9, 'reorder_point' => 3]);
    }

    public function test_actualizar_solo_el_nombre_de_una_ubicacion_con_peso_y_volumen(): void
    {
        $location = WarehouseLocation::factory()->create(['max_weight' => 120.5, 'max_volume' => 3.25]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('warehouse-locations')
            ->withData(['type' => 'warehouse-locations', 'id' => (string) $location->id, 'attributes' => ['name' => 'Estante renombrado']])
            ->patch("/api/v1/warehouse-locations/{$location->id}")
            ->assertOk();

        $this->assertDatabaseHas('warehouse_locations', ['id' => $location->id, 'name' => 'Estante renombrado', 'max_weight' => 120.5]);
    }
}
