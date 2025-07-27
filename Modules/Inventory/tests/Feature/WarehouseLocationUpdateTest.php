<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseLocationUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermissions(string $roleName = 'test_role', array $permissions = []): User
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

    public function test_admin_can_update_warehouse_location()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.update']);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Original Location'
        ]);

        $updateData = [
            'type' => 'warehouse-locations',
            'id' => (string) $location->id,
            'attributes' => [
                'name' => 'Updated Location Name',
                'priority' => 10,
                'isActive' => false,
                'maxWeight' => 1500.50,
                'maxVolume' => 750.25
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($updateData)
            ->patch("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(200);
        
        // Verificar que los campos se actualizaron
        $location->refresh();
        $this->assertEquals('Updated Location Name', $location->name);
        $this->assertEquals(10, $location->priority);
        $this->assertFalse($location->is_active);
        $this->assertEquals(1500.50, $location->max_weight);
        $this->assertEquals(750.25, $location->max_volume);
    }

    public function test_warehouse_location_update_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.update']);
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $invalidData = [
            'type' => 'warehouse-locations',
            'id' => (string) $location->id,
            'attributes' => [
                'name' => '', // Empty name should fail
                'locationType' => 'invalid_type' // Invalid location type
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($invalidData)
            ->patch("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/locationType']
        ]);
    }

    public function test_warehouse_location_update_validates_unique_code_when_changed()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.update']);
        
        $warehouse = Warehouse::factory()->create();
        
        // Crear dos locations
        $existingLocation = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'EXISTING_CODE'
        ]);
        $locationToUpdate = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'UPDATE_ME'
        ]);

        $invalidData = [
            'type' => 'warehouse-locations',
            'id' => (string) $locationToUpdate->id,
            'attributes' => [
                'name' => 'Updated Name',
                'code' => 'EXISTING_CODE' // This code already exists
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($invalidData)
            ->patch("/api/v1/warehouse-locations/{$locationToUpdate->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
    }

    public function test_warehouse_location_update_validates_unique_barcode_when_changed()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.update']);
        
        $warehouse = Warehouse::factory()->create();
        
        // Crear dos locations
        $existingLocation = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'barcode' => 'EXISTING_BARCODE'
        ]);
        $locationToUpdate = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'barcode' => 'UPDATE_BARCODE'
        ]);

        $invalidData = [
            'type' => 'warehouse-locations',
            'id' => (string) $locationToUpdate->id,
            'attributes' => [
                'name' => 'Updated Name',
                'barcode' => 'EXISTING_BARCODE' // This barcode already exists
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($invalidData)
            ->patch("/api/v1/warehouse-locations/{$locationToUpdate->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/barcode']
        ]);
    }

    public function test_warehouse_location_update_returns_404_for_nonexistent_location()
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.update']);

        $updateData = [
            'type' => 'warehouse-locations',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($updateData)
            ->patch("/api/v1/warehouse-locations/99999");

        $response->assertStatus(404);
    }

    public function test_tech_cannot_update_warehouse_location()
    {
        $tech = $this->createUserWithPermissions('tech', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $updateData = [
            'type' => 'warehouse-locations',
            'id' => (string) $location->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($updateData)
            ->patch("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_warehouse_location()
    {
        $customer = $this->createUserWithPermissions('customer', []); // No permissions
        
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $updateData = [
            'type' => 'warehouse-locations',
            'id' => (string) $location->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('warehouse-locations')
            ->withData($updateData)
            ->patch("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_warehouse_location()
    {
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $updateData = [
            'type' => 'warehouse-locations',
            'id' => (string) $location->id,
            'attributes' => [
                'name' => 'Updated Name'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($updateData)
            ->patch("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(401);
    }
}
