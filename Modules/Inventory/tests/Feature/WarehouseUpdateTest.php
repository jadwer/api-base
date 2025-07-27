<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseUpdateTest extends TestCase
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

    public function test_admin_can_update_warehouse()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.update']);
        
        // Crear warehouse con datos completos usando factory
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'type' => 'warehouses',
            'id' => (string) $warehouse->id,
            'attributes' => [
                'name' => 'Updated Warehouse Name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($updateData)
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200);
        
        // Verificar que solo el nombre se actualizó
        $warehouse->refresh();
        $this->assertEquals('Updated Warehouse Name', $warehouse->name);
    }

    public function test_warehouse_update_validates_required_fields()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.update']);
        
        $warehouse = Warehouse::factory()->create();

        $invalidData = [
            'type' => 'warehouses',
            'id' => (string) $warehouse->id,
            'attributes' => [
                'name' => '', // Empty name should fail
                'email' => 'invalid-email' // Invalid email should fail
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($invalidData)
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/email']
        ]);
    }

    public function test_warehouse_update_validates_unique_code_when_changed()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.update']);
        
        // Crear dos warehouses
        $existingWarehouse = Warehouse::factory()->create(['code' => 'EXISTING_CODE']);
        $warehouseToUpdate = Warehouse::factory()->create(['code' => 'UPDATE_ME']);

        $invalidData = [
            'type' => 'warehouses',
            'id' => (string) $warehouseToUpdate->id,
            'attributes' => [
                'name' => 'Updated Name',
                'code' => 'EXISTING_CODE' // This code already exists
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($invalidData)
            ->patch("/api/v1/warehouses/{$warehouseToUpdate->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
    }

    public function test_warehouse_update_returns_404_for_nonexistent_warehouse()
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.update']);

        $updateData = [
            'type' => 'warehouses',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($updateData)
            ->patch("/api/v1/warehouses/99999");

        $response->assertStatus(404);
    }

    public function test_tech_cannot_update_warehouse()
    {
        $tech = $this->createUserWithWarehousePermissions('tech', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'type' => 'warehouses',
            'id' => (string) $warehouse->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($updateData)
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_warehouse()
    {
        $customer = $this->createUserWithWarehousePermissions('customer', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'type' => 'warehouses',
            'id' => (string) $warehouse->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData($updateData)
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'type' => 'warehouses',
            'id' => (string) $warehouse->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($updateData)
            ->patch("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(401);
    }
}
