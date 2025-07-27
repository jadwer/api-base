<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseLocationStoreTest extends TestCase
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

    public function test_admin_can_create_warehouse_location(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.store']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'New Location',
                'code' => 'NEW-001',
                'description' => 'A new location for testing',
                'locationType' => 'shelf',
                'aisle' => 'A1',
                'rack' => 'R01',
                'shelf' => 'S01',
                'level' => 'L1',
                'position' => '001',
                'barcode' => 'BC123456',
                'maxWeight' => 1000.50,
                'maxVolume' => 50.25,
                'dimensions' => '2x1x3',
                'isActive' => true,
                'isPickable' => true,
                'isReceivable' => true,
                'priority' => 5,
                'metadata' => [
                    'zone' => 'Z1',
                    'access_level' => 'public'
                ]
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertCreated();
        
        $this->assertDatabaseHas('warehouse_locations', [
            'name' => 'New Location',
            'code' => 'NEW-001',
            'warehouse_id' => $warehouse->id,
            'location_type' => 'shelf',
            'is_active' => true,
        ]);

        $data = $response->json('data');
        $this->assertEquals('New Location', $data['attributes']['name']);
        $this->assertEquals('NEW-001', $data['attributes']['code']);
        $this->assertEquals('shelf', $data['attributes']['locationType']);
    }

    public function test_warehouse_location_creation_validates_required_fields(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.store']);
        $this->actingAs($admin, 'sanctum');

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                // Missing required fields: name, code, locationType, warehouse relationship
                'description' => 'Incomplete location'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/locationType']
        ]);
    }

    public function test_warehouse_location_creation_validates_unique_code(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.store']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create();
        
        // Create existing location
        WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'DUPLICATE-001'
        ]);

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'Duplicate Location',
                'code' => 'DUPLICATE-001', // Same code
                'locationType' => 'bin'
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/code']
        ]);
    }

    public function test_warehouse_location_creation_validates_location_type(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['warehouse-locations.store']);
        $this->actingAs($admin, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'Invalid Type Location',
                'code' => 'INVALID-001',
                'locationType' => 'invalid_type' // Invalid type
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/locationType']
        ]);
    }

    public function test_tech_cannot_create_warehouse_location(): void
    {
        $tech = $this->createUserWithPermissions('tech', ['warehouse-locations.index']); // No store permission
        $this->actingAs($tech, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'Unauthorized Location',
                'code' => 'UNAUTH-001',
                'locationType' => 'bin'
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_warehouse_location(): void
    {
        $customer = $this->createUserWithPermissions('customer', []); // No permissions
        $this->actingAs($customer, 'sanctum');

        $warehouse = Warehouse::factory()->create();

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'Customer Location',
                'code' => 'CUST-001',
                'locationType' => 'bin'
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_warehouse_location(): void
    {
        $warehouse = Warehouse::factory()->create();

        $locationData = [
            'type' => 'warehouse-locations',
            'attributes' => [
                'name' => 'Unauthenticated Location',
                'code' => 'UNAUTH-001',
                'locationType' => 'bin'
            ],
            'relationships' => [
                'warehouse' => [
                    'data' => [
                        'type' => 'warehouses',
                        'id' => (string) $warehouse->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('warehouse-locations')
            ->withData($locationData)
            ->post('/api/v1/warehouse-locations');

        $response->assertStatus(401);
    }
}
