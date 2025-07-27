<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createUserWithWarehousePermissions(string $roleName, array $permissions): User
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

    public function test_admin_can_list_warehouses(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.index']);
        $this->actingAs($admin, 'sanctum');

        // Create test data
        Warehouse::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/warehouses');

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
                        'address',
                        'city',
                        'state',
                        'country',
                        'postalCode',
                        'phone',
                        'email',
                        'isActive',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);
    }

    public function test_admin_can_sort_warehouses_by_name(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.index']);
        $this->actingAs($admin, 'sanctum');

        // Clear all existing warehouses first
        \Modules\Inventory\Models\Warehouse::query()->delete();
        
        // Create warehouses with specific names for sorting
        Warehouse::factory()->create(['name' => 'Z Warehouse']);
        Warehouse::factory()->create(['name' => 'A Warehouse']);

        $response = $this->jsonApi()->get('/api/v1/warehouses?sort=name');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('A Warehouse', $data[0]['attributes']['name']);
        $this->assertEquals('Z Warehouse', $data[1]['attributes']['name']);
    }

    public function test_admin_can_filter_warehouses_by_active_status(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.index']);
        $this->actingAs($admin, 'sanctum');

        // Clear all existing warehouses first
        \Modules\Inventory\Models\Warehouse::query()->delete();

        // Create active and inactive warehouses
        Warehouse::factory()->create(['is_active' => true, 'name' => 'Active Warehouse']);
        Warehouse::factory()->create(['is_active' => false, 'name' => 'Inactive Warehouse']);

        $response = $this->jsonApi()->get('/api/v1/warehouses?filter[is_active]=1');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['attributes']['isActive']);
    }

    public function test_tech_can_list_warehouses(): void
    {
        $tech = $this->createUserWithWarehousePermissions('tech', ['warehouses.index']);
        $this->actingAs($tech, 'sanctum');

        // Clear all existing warehouses first
        \Modules\Inventory\Models\Warehouse::query()->delete();

        Warehouse::factory()->count(2)->create();

        $response = $this->jsonApi()->get('/api/v1/warehouses');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_cannot_list_warehouses(): void
    {
        // Create customer user WITHOUT warehouse permissions
        $customer = User::factory()->create(['email' => 'test-customer@example.com']);
        $customerRole = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'api'
        ]);
        $customer->assignRole($customerRole);
        
        // Revoke warehouse permissions from the customer ROLE (not just user)
        $warehousePermissions = \Modules\PermissionManager\Models\Permission::where('name', 'like', 'warehouses.%')->get();
        foreach ($warehousePermissions as $permission) {
            if ($customerRole->hasPermissionTo($permission)) {
                $customerRole->revokePermissionTo($permission);
            }
        }
        
        $this->actingAs($customer, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/warehouses');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_warehouses(): void
    {
        $response = $this->jsonApi()->get('/api/v1/warehouses');

        $response->assertStatus(401);
    }
}
