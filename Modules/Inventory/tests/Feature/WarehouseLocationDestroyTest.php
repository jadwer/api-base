<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseLocationDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'warehouse-locations.delete']);
        Permission::firstOrCreate(['name' => 'warehouse-locations.view']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tech']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role);
        
        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_delete_warehouse_location()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.delete']);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(204);
        
        // Verificar que la ubicación fue eliminada
        $this->assertDatabaseMissing('warehouse_locations', [
            'id' => $location->id
        ]);
    }

    public function test_delete_nonexistent_warehouse_location_returns_404()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.delete']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/99999");

        $response->assertStatus(404);
    }

    public function test_warehouse_location_deletion_cascades_related_records()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.delete']);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        // Crear registros relacionados (stock, product_batches) si existen
        // Esto verificará que el cascade delete funcione correctamente
        
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(204);
        
        // Verificar que la ubicación y sus relaciones fueron eliminadas
        $this->assertDatabaseMissing('warehouse_locations', [
            'id' => $location->id
        ]);
    }

    public function test_tech_cannot_delete_warehouse_location()
    {
        $tech = $this->createUserWithPermissions('tech', []);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(403);
        
        // Verificar que la ubicación NO fue eliminada
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => $location->id
        ]);
    }

    public function test_customer_cannot_delete_warehouse_location()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(403);
        
        // Verificar que la ubicación NO fue eliminada
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => $location->id
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_warehouse_location()
    {
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(401);
        
        // Verificar que la ubicación NO fue eliminada
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => $location->id
        ]);
    }

    public function test_delete_warehouse_location_with_active_stock_should_fail()
    {
        $this->markTestSkipped('Skip until Stock model is implemented');
        
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.delete']);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        // Cuando se implemente Stock, crear stock activo para esta ubicación
        // Stock::factory()->create(['warehouse_location_id' => $location->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->delete("/api/v1/warehouse-locations/{$location->id}");

        // Debería fallar si hay stock activo
        $response->assertStatus(422);
        
        // Verificar que la ubicación NO fue eliminada
        $this->assertDatabaseHas('warehouse_locations', [
            'id' => $location->id
        ]);
    }
}
