<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseLocationShowTest extends TestCase
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

    public function test_admin_can_show_warehouse_location(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.view']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Test Location',
            'code' => 'TEST-001',
            'location_type' => 'shelf',
        ]);

        $response = $this->jsonApi()->get("/api/v1/warehouse-locations/{$location->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                'links'
            ],
            'jsonapi'
        ]);
        
        $data = $response->json('data');
        $this->assertEquals('Test Location', $data['attributes']['name']);
        $this->assertEquals('TEST-001', $data['attributes']['code']);
        $this->assertEquals('shelf', $data['attributes']['locationType']);
    }

    public function test_admin_can_show_warehouse_location_with_relationships(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.view']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create(['name' => 'Main Warehouse']);
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Location with Warehouse'
        ]);

        $response = $this->jsonApi()
            ->includePaths('warehouse')
            ->get("/api/v1/warehouse-locations/{$location->id}");

        $response->assertOk();
        
        // Verificar que se incluye la relación warehouse
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'warehouse' => [
                        'data' => [
                            'id',
                            'type'
                        ]
                    ]
                ]
            ],
            'included' => [
                '*' => [
                    'id',
                    'type',
                    'attributes'
                ]
            ]
        ]);
    }

    public function test_tech_can_show_warehouse_location(): void
    {
        $tech = $this->createUserWithPermissions('tech', ['warehouse-locations.view']);
        $this->actingAs($tech, 'sanctum');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->jsonApi()->get("/api/v1/warehouse-locations/{$location->id}");

        $response->assertOk();
    }

    public function test_customer_cannot_show_warehouse_location(): void
    {
        $customer = $this->createUserWithPermissions('customer', []); // No permissions
        $this->actingAs($customer, 'sanctum');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->jsonApi()->get("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_show_warehouse_location(): void
    {
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->jsonApi()->get("/api/v1/warehouse-locations/{$location->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_warehouse_location_returns_404(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.view']);
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/warehouse-locations/99999');

        $response->assertStatus(404);
    }
}
