<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithWarehousePermissions(string $roleName = 'test_role', array $permissions = []): User
    {
        $user = User::factory()->create();
        
        $role = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => $roleName . '_' . uniqid(),
            'guard_name' => 'api'
        ]);
        
        foreach ($permissions as $permissionName) {
            $permission = \Modules\PermissionManager\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
            $role->givePermissionTo($permission);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_delete_warehouse()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.destroy']);
        
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(204); // No Content for successful deletion
        
        // Verificar que el warehouse fue eliminado
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    public function test_delete_nonexistent_warehouse_returns_404()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/99999");

        $response->assertStatus(404);
    }

    public function test_tech_cannot_delete_warehouse()
    {
        $tech = $this->createUserWithWarehousePermissions('tech', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(403);
        
        // Verificar que el warehouse NO fue eliminado
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_customer_cannot_delete_warehouse()
    {
        $customer = $this->createUserWithWarehousePermissions('customer', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(403);
        
        // Verificar que el warehouse NO fue eliminado
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_unauthenticated_user_cannot_delete_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(401);
        
        // Verificar que el warehouse NO fue eliminado
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_can_delete_warehouse_with_related_data_cascade()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.destroy']);
        
        $warehouse = Warehouse::factory()->create();
        
        // Crear ubicaciones relacionadas
        $location = $warehouse->locations()->create([
            'name' => 'Location A',
            'code' => 'LOC-A',
            'location_type' => 'shelf',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->delete("/api/v1/warehouses/{$warehouse->id}");

        // Debe ser exitoso por CASCADE DELETE
        $response->assertStatus(204); // No Content for successful deletion
        
        // Verificar que el warehouse fue eliminado
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
        
        // Verificar que las ubicaciones también fueron eliminadas (CASCADE)
        $this->assertDatabaseMissing('warehouse_locations', ['id' => $location->id]);
    }
}
