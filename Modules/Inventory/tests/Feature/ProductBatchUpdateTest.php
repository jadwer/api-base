<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductBatchUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'product-batches.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'product-batches.view', 'guard_name' => 'api']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role);
        
        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_update_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $productBatch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'batch_number' => 'OLD001',
            'status' => 'active',
            'current_quantity' => 100.0,
            'reserved_quantity' => 10.0
        ]);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'batchNumber' => 'UPDATED001',
                    'currentQuantity' => 80.0,
                    'reservedQuantity' => 15.0,
                    'status' => 'quarantine',
                    'qualityNotes' => 'Updated quality notes',
                    'testResults' => [
                        'ph' => 7.5,
                        'quality_grade' => 'B'
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'batchNumber',
                    'currentQuantity',
                    'reservedQuantity',
                    'availableQuantity',
                    'status',
                    'qualityNotes',
                    'testResults'
                ]
            ]
        ]);
        
        $this->assertEquals('UPDATED001', $response->json('data.attributes.batchNumber'));
        $this->assertEquals('quarantine', $response->json('data.attributes.status'));
        $this->assertEquals(65.0000, $response->json('data.attributes.availableQuantity')); // 80 - 15
        
        // Verificar que se actualizó en la base de datos
        $this->assertDatabaseHas('product_batches', [
            'id' => $productBatch->id,
            'batch_number' => 'UPDATED001',
            'current_quantity' => 80.0000,
            'reserved_quantity' => 15.0000,
            'status' => 'quarantine'
        ]);
    }

    public function test_admin_can_update_partial_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $productBatch = ProductBatch::factory()->create([
            'batch_number' => 'PARTIAL001',
            'status' => 'active',
            'quality_notes' => 'Original notes'
        ]);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'status' => 'expired',
                    'qualityNotes' => 'Updated notes only'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $this->assertEquals('expired', $response->json('data.attributes.status'));
        $this->assertEquals('Updated notes only', $response->json('data.attributes.qualityNotes'));
        
        // Verificar que otros campos no cambiaron
        $this->assertEquals('PARTIAL001', $response->json('data.attributes.batchNumber'));
    }

    public function test_update_validates_unique_batch_number()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        // Crear dos batches
        $batch1 = ProductBatch::factory()->create(['batch_number' => 'EXIST001']);
        $batch2 = ProductBatch::factory()->create(['batch_number' => 'EXIST002']);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $batch2->id,
                'attributes' => [
                    'batchNumber' => 'EXIST001' // Intentar usar el número del primer batch
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$batch2->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/batchNumber'
        ], $response);
    }

    public function test_update_allows_same_batch_number_for_same_record()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $productBatch = ProductBatch::factory()->create([
            'batch_number' => 'SAME001'
        ]);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'batchNumber' => 'SAME001', // Mismo número
                    'status' => 'quarantine'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $this->assertEquals('quarantine', $response->json('data.attributes.status'));
    }

    public function test_update_validates_date_constraints()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $productBatch = ProductBatch::factory()->create([
            'manufacturing_date' => '2025-06-01'
        ]);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'expirationDate' => '2025-01-01' // Antes de manufacturing_date
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/expirationDate'
        ], $response);
    }

    public function test_update_validates_quantity_constraints()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $productBatch = ProductBatch::factory()->create([
            'initial_quantity' => 50.0000
        ]);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'currentQuantity' => 100.0000 // Mayor que initial_quantity
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/currentQuantity'
        ], $response);
    }

    public function test_update_validates_status_enum()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);
        
        $productBatch = ProductBatch::factory()->create();

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'status' => 'invalid_status'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/status'
        ], $response);
    }

    public function test_unauthorized_user_cannot_update_product_batch()
    {
        $productBatch = ProductBatch::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'status' => 'expired'
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_update_product_batch()
    {
        $user = $this->createUserWithPermissions('customer', []);
        $productBatch = ProductBatch::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => (string) $productBatch->id,
                'attributes' => [
                    'status' => 'expired'
                ]
            ]
        ];
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.update']);

        $data = [
            'data' => [
                'type' => 'product-batches',
                'id' => '99999',
                'attributes' => [
                    'status' => 'expired'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->patch('/api/v1/product-batches/99999');

        $response->assertStatus(404);
    }
}
