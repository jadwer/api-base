<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseLocationIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermissions(string $roleName, array $permissions): User
    {
        $user = User::factory()->create([
            'email' => "test-{$roleName}@example.com",
            'name' => "Test " . ucfirst($roleName)
        ]);
        
        $role = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => $roleName,
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

    public function test_admin_can_list_warehouse_locations(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.index']);
        $this->actingAs($admin, 'sanctum');

        // Create test data
        $warehouse = Warehouse::factory()->create();
        WarehouseLocation::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'description',
                        'locationType',
                        'aisle',
                        'rack',
                        'shelf',
                        'level',
                        'position',
                        'barcode',
                        'maxWeight',
                        'maxVolume',
                        'dimensions',
                        'isActive',
                        'isPickable',
                        'isReceivable',
                        'priority',
                        'metadata',
                        'createdAt',
                        'updatedAt',
                    ],
                    'relationships',
                ],
            ],
            'jsonapi',
        ]);
    }

    public function test_admin_can_sort_warehouse_locations_by_name(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.index']);
        $this->actingAs($admin, 'sanctum');

        // Clear existing locations
        \Modules\Inventory\Models\WarehouseLocation::query()->delete();
        
        $warehouse = Warehouse::factory()->create();
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'name' => 'Z Location']);
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'name' => 'A Location']);

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations?sort=name');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('A Location', $data[0]['attributes']['name']);
        $this->assertEquals('Z Location', $data[1]['attributes']['name']);
    }

    public function test_admin_can_filter_warehouse_locations_by_active_status(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.index']);
        $this->actingAs($admin, 'sanctum');

        // Clear existing locations
        \Modules\Inventory\Models\WarehouseLocation::query()->delete();

        $warehouse = Warehouse::factory()->create();
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'is_active' => true, 'name' => 'Active Location']);
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'is_active' => false, 'name' => 'Inactive Location']);

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations?filter[is_active]=1');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['attributes']['isActive']);
    }

    public function test_admin_can_filter_warehouse_locations_by_warehouse(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.index']);
        $this->actingAs($admin, 'sanctum');

        // Clear existing locations
        \Modules\Inventory\Models\WarehouseLocation::query()->delete();

        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse1->id]);
        WarehouseLocation::factory()->create(['warehouse_id' => $warehouse2->id]);

        $response = $this->jsonApi()->get("/api/v1/warehouse-locations?filter[warehouse_id]={$warehouse1->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_tech_can_list_warehouse_locations(): void
    {
        $tech = $this->createUserWithPermissions('tech', ['warehouse-locations.index']);
        $this->actingAs($tech, 'sanctum');

        // Clear existing locations
        \Modules\Inventory\Models\WarehouseLocation::query()->delete();

        $warehouse = Warehouse::factory()->create();
        WarehouseLocation::factory()->count(2)->create(['warehouse_id' => $warehouse->id]);

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_cannot_list_warehouse_locations(): void
    {
        $customer = $this->createUserWithPermissions('customer', []); // No permissions
        $this->actingAs($customer, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_warehouse_locations(): void
    {
        $response = $this->jsonApi()->get('/api/v1/warehouse-locations');

        $response->assertStatus(401);
    }
}
