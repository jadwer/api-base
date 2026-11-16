<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Tests\TestCase;

/** Almacenes del rediseno de Inventario (2026-11-15): direccion desglosada, ubicaciones y busqueda. */
class WarehouseRedesignTest extends TestCase
{
    public function test_guarda_y_devuelve_la_direccion_desglosada(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum');

        $response = $this->jsonApi()->expects('warehouses')->withData([
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'Almacén Norte', 'slug' => 'almacen-norte', 'code' => 'ALM-N01', 'warehouseType' => 'secondary',
                'street' => 'Av. Madero Sur', 'exteriorNumber' => '1234', 'interiorNumber' => 'B', 'neighborhood' => 'Centro',
                'municipality' => 'Morelia', 'state' => 'Michoacán', 'country' => 'México', 'postalCode' => '58000',
                'addressReference' => 'Frente a la escuela',
            ],
        ])->post('/api/v1/warehouses');

        $response->assertCreated();
        $id = $response->json('data.id');
        $this->assertDatabaseHas('warehouses', ['id' => $id, 'street' => 'Av. Madero Sur', 'exterior_number' => '1234', 'municipality' => 'Morelia', 'address_reference' => 'Frente a la escuela']);
        $this->jsonApi()->expects('warehouses')->get("/api/v1/warehouses/{$id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.neighborhood', 'Centro')
            ->assertJsonPath('data.attributes.locationsCount', 0);
    }

    public function test_el_listado_trae_cuantas_ubicaciones_tiene_y_busca_por_nombre_codigo_o_ciudad(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum');
        $norte = Warehouse::factory()->create(['name' => 'Bodega Norte', 'code' => 'BN-01', 'city' => 'Tlacotalpan']);
        Warehouse::factory()->create(['name' => 'Bodega Sur', 'code' => 'BSX-77', 'city' => 'Mérida']);
        WarehouseLocation::factory()->count(3)->create(['warehouse_id' => $norte->id]);

        $byCity = $this->jsonApi()->expects('warehouses')->get('/api/v1/warehouses?filter[search]=Tlacotalpan')->assertOk()->json('data');
        $this->assertSame([(string) $norte->id], array_column($byCity, 'id'));
        $this->assertSame(3, $byCity[0]['attributes']['locationsCount']);

        $byCode = $this->jsonApi()->expects('warehouses')->get('/api/v1/warehouses?filter[search]=BSX-77')->assertOk()->json('data');
        $this->assertSame(['Bodega Sur'], array_column(array_column($byCode, 'attributes'), 'name'));
    }

    public function test_un_numero_exterior_demasiado_largo_se_rechaza_en_espanol(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum');

        $this->jsonApi()->expects('warehouses')->withData([
            'type' => 'warehouses',
            'attributes' => ['name' => 'X', 'slug' => 'x', 'code' => 'X-1', 'warehouseType' => 'main', 'exteriorNumber' => str_repeat('9', 31)],
        ])->post('/api/v1/warehouses')
            ->assertStatus(422)
            ->assertJsonFragment(['detail' => 'El campo número exterior no debe tener más de 30 caracteres.']);
    }
}
