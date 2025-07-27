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

class ProductBatchIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios (con firstOrCreate)
        Permission::firstOrCreate(['name' => 'product-batches.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'product-batches.view', 'guard_name' => 'api']);
        
        // Crear roles (con firstOrCreate)
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

    public function test_admin_can_list_product_batches()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.index']);
        
        // Crear datos necesarios
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        ProductBatch::factory()->count(3)->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'batchNumber',
                        'lotNumber',
                        'manufacturingDate',
                        'expirationDate',
                        'initialQuantity',
                        'currentQuantity',
                        'reservedQuantity',
                        'availableQuantity',
                        'unitCost',
                        'totalValue',
                        'status',
                        'supplierName',
                        'supplierBatch',
                        'qualityNotes',
                        'testResults',
                        'certifications',
                        'metadata',
                        'createdAt',
                        'updatedAt'
                    ],
                    'relationships' => [
                        'product',
                        'warehouse',
                        'warehouseLocation'
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_product_batches_by_status()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.index']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        // Crear batches con diferentes estados
        ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active'
        ]);
        
        ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'expired'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->filter(['status' => 'active'])
            ->get('/api/v1/product-batches');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('active', $response->json('data.0.attributes.status'));
    }

    public function test_admin_can_include_relationships()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.index']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->includePaths('product', 'warehouse', 'warehouseLocation')
            ->get('/api/v1/product-batches');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'relationships' => [
                        'product' => ['data'],
                        'warehouse' => ['data'],
                        'warehouseLocation' => ['data']
                    ]
                ]
            ],
            'included'
        ]);
    }

    public function test_admin_can_sort_product_batches_by_expiration_date()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.index']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        // Crear batches con diferentes fechas de vencimiento
        $batch1 = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'AAA001',
            'expiration_date' => now()->addDays(10)
        ]);
        
        $batch2 = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BBB001',
            'expiration_date' => now()->addDays(30)
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->sort('expirationDate')
            ->get('/api/v1/product-batches');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        // El primero debe ser el que vence más pronto
        $this->assertEquals($batch1->id, $response->json('data.0.id'));
    }

    public function test_unauthorized_user_cannot_list_product_batches()
    {
        $response = $this->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches');
            
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_product_batches()
    {
        $user = $this->createUserWithPermissions('customer', []); // Sin permisos
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches');
            
        $response->assertStatus(403);
    }

    public function test_tech_with_limited_permissions_can_list_product_batches()
    {
        $tech = $this->createUserWithPermissions('tech', ['product-batches.index']);
        
        ProductBatch::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
