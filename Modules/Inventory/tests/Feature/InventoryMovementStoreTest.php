<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class InventoryMovementStoreTest extends TestCase
{
    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::firstOrCreate(['name' => $role]);
        
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }



    public function test_admin_can_create_entry_movement(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['inventory-movements.store']);
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'description' => 'Test entry movement',
                'quantity' => 100.5,
                'unitCost' => 25.75,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertCreated();
        
        $id = $response->json('data.id');
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $id,
            'movement_type' => 'entry',
            'quantity' => 100.5000,
            'unit_cost' => 25.75,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id
        ]);
    }

    public function test_admin_can_create_exit_movement(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'exit',
                'movementDate' => '2024-08-13T10:00:00Z',
                'description' => 'Test exit movement',
                'quantity' => 50,
                'unitCost' => 25.75,
                'referenceType' => 'sale',
                'referenceId' => 123
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertCreated();
        
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'exit',
            'reference_type' => 'sale',
            'reference_id' => 123,
            'quantity' => 50.0000
        ]);
    }

    public function test_admin_can_create_transfer_movement(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $sourceWarehouse = Warehouse::factory()->create(['name' => 'Source Warehouse']);
        $destinationWarehouse = Warehouse::factory()->create(['name' => 'Destination Warehouse']);
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $sourceWarehouse->id,
                'destinationWarehouseId' => $destinationWarehouse->id,
                'userId' => $admin->id,
                'movementType' => 'transfer',
                'movementDate' => '2024-08-13T10:00:00Z',
                'description' => 'Test transfer movement',
                'quantity' => 75,
                'unitCost' => 15.50
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertCreated();
        
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'transfer',
            'warehouse_id' => $sourceWarehouse->id,
            'destination_warehouse_id' => $destinationWarehouse->id,
            'quantity' => 75.0000
        ]);
    }

    public function test_customer_user_cannot_create_inventory_movement(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $customer->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 100,
                'unitCost' => 25.75
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertForbidden();
    }

    public function test_cannot_create_movement_without_required_fields(): void
    {
        $admin = $this->getAdminUser();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'description' => 'Missing required fields'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertStatus(422);
        // Verificar que es un error de validación
        $response->assertJsonStructure([
            'errors' => [
                '*' => [
                    'detail',
                    'status',
                    'source'
                ]
            ]
        ]);
    }

    public function test_cannot_create_transfer_without_destination_warehouse(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'transfer',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 50,
                'unitCost' => 25.75
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertStatus(422);
    }

    public function test_cannot_create_movement_with_zero_quantity(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 0,
                'unitCost' => 25.75
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertStatus(422);
        // Verificar que es un error de validación para quantity
        $response->assertJsonStructure([
            'errors' => [
                '*' => [
                    'detail',
                    'status',
                    'source'
                ]
            ]
        ]);
    }

    public function test_can_create_movement_with_batch_info(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'type' => 'inventory-movements',
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 100,
                'unitCost' => 25.75,
                'batchInfo' => [
                    'batch_number' => 'BATCH-001',
                    'expiry_date' => '2025-12-31'
                ],
                'metadata' => [
                    'source' => 'import',
                    'temperature' => 15.5
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($data)
            ->post('/api/v1/inventory-movements');

        $response->assertCreated();
        
        $movement = InventoryMovement::find($response->json('data.id'));
        $this->assertEquals('BATCH-001', $movement->batch_info['batch_number']);
        $this->assertEquals('import', $movement->metadata['source']);
    }
}