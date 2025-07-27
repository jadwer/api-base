<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseStoreTest extends TestCase
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

    public function test_admin_can_create_warehouse(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.store']);
        $this->actingAs($admin, 'sanctum');

        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'New Warehouse',
                'slug' => 'new-warehouse',
                'code' => 'NEW-001',
                'description' => 'A new warehouse for testing',
                'address' => '123 Test Street',
                'city' => 'Test City',
                'state' => 'Test State',
                'country' => 'Test Country',
                'postalCode' => '12345',
                'phone' => '+1234567890',
                'email' => 'test@warehouse.com',
                'managerName' => 'Test Manager',
                'maxCapacity' => 5000.00,
                'capacityUnit' => 'm2',
                'warehouseType' => 'main',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertCreated();
        
        $this->assertDatabaseHas('warehouses', [
            'name' => 'New Warehouse',
            'code' => 'NEW-001',
            'is_active' => true
        ]);

        $data = $response->json('data');
        $this->assertEquals('New Warehouse', $data['attributes']['name']);
        $this->assertEquals('NEW-001', $data['attributes']['code']);
    }

    public function test_warehouse_creation_validates_required_fields(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.store']);
        $this->actingAs($admin, 'sanctum');

        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                // Missing required fields
                'description' => 'Incomplete warehouse'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
    }

    public function test_warehouse_creation_validates_unique_code(): void
    {
        $admin = $this->createUserWithWarehousePermissions('admin', ['warehouses.store']);
        $this->actingAs($admin, 'sanctum');

        // Create existing warehouse
        Warehouse::factory()->create(['code' => 'DUPLICATE-001']);

        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'Duplicate Warehouse',
                'code' => 'DUPLICATE-001', // Same code
                'warehouseType' => 'main'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
    }

    public function test_tech_cannot_create_warehouse(): void
    {
        $tech = $this->createUserWithWarehousePermissions('tech', ['warehouses.index']); // No store permission
        $this->actingAs($tech, 'sanctum');

        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'Unauthorized Warehouse',
                'code' => 'UNAUTH-001',
                'warehouseType' => 'main'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_warehouse(): void
    {
        $customer = User::factory()->create(['email' => 'test-customer@example.com']);
        $customerRole = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'api'
        ]);
        $customer->assignRole($customerRole);
        
        $this->actingAs($customer, 'sanctum');

        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'Customer Warehouse',
                'code' => 'CUST-001',
                'warehouseType' => 'main'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_warehouse(): void
    {
        $warehouseData = [
            'type' => 'warehouses',
            'attributes' => [
                'name' => 'Unauthenticated Warehouse',
                'code' => 'UNAUTH-001',
                'warehouseType' => 'main'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouses')
            ->withData($warehouseData)
            ->post('/api/v1/warehouses');

        $response->assertStatus(401);
    }
}
