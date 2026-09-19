<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Inventory\Models\Warehouse;
use Tests\TestCase;

/**
 * Inventario compartido pero etiquetado: cada almacen pertenece a una sucursal.
 */
class WarehouseBranchTest extends TestCase
{
    public function test_warehouse_created_without_branch_belongs_to_main(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->assertSame(Branch::mainId(), $warehouse->fresh()->branch_id);
        $this->assertSame(Branch::MAIN_CODE, $warehouse->branch->code);
    }

    public function test_warehouse_show_includes_branch(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $warehouse = Warehouse::factory()->create(['branch_id' => $toluca->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->includePaths('branch')
            ->get("/api/v1/warehouses/{$warehouse->id}");

        $response->assertFetchedOne($warehouse);
        $response->assertIsIncluded('branches', $toluca);
    }

    public function test_warehouses_can_be_filtered_by_branch(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $inToluca = Warehouse::factory()->create(['branch_id' => $toluca->id]);
        Warehouse::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->filter(['branch' => $toluca->id])
            ->get('/api/v1/warehouses');

        $response->assertFetchedMany([$inToluca]);
    }

    public function test_branch_can_be_assigned_through_the_api(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData([
                'type' => 'warehouses',
                'id' => (string) $warehouse->id,
                'relationships' => [
                    'branch' => ['data' => ['type' => 'branches', 'id' => (string) $toluca->id]],
                ],
            ])
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertFetchedOne($warehouse);
        $this->assertSame($toluca->id, $warehouse->fresh()->branch_id);
    }
}
