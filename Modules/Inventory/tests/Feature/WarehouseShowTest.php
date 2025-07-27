<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseShowTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_show_warehouse(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.view']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create([
            'name' => 'Test Warehouse',
            'code' => 'TEST-001'
        ]);

        $response = $this->jsonApi()->get("/api/v1/warehouses/{$warehouse->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                'relationships',
                'links'
            ],
            'jsonapi'
        ]);
        
        $data = $response->json('data');
        $this->assertEquals('Test Warehouse', $data['attributes']['name']);
        $this->assertEquals('TEST-001', $data['attributes']['code']);
    }

    public function test_tech_can_show_warehouse(): void
    {
        $tech = $this->createUserWithWarehousePermissions('tech', ['warehouses.view']);
        $this->actingAs($tech, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/warehouses/{$warehouse->id}");

        $response->assertOk();
    }

    public function test_customer_cannot_show_warehouse(): void
    {
        $customer = User::factory()->create(['email' => 'test-customer@example.com']);
        $customerRole = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'api'
        ]);
        $customer->assignRole($customerRole);
        
        $this->actingAs($customer, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_show_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_warehouse_returns_404(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.view']);
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/warehouses/99999');

        $response->assertStatus(404);
    }
}
