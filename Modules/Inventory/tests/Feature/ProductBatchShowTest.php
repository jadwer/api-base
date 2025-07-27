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

class ProductBatchShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'product-batches.index', 'guard_name' => 'api']);
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

    public function test_admin_can_view_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.view']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $productBatch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'batch_number' => 'TEST001',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
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
        ]);
        
        $this->assertEquals($productBatch->id, $response->json('data.id'));
        $this->assertEquals('TEST001', $response->json('data.attributes.batchNumber'));
        $this->assertEquals('active', $response->json('data.attributes.status'));
    }

    public function test_admin_can_view_product_batch_with_includes()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.view']);
        
        $product = Product::factory()->create(['name' => 'Test Product']);
        $warehouse = Warehouse::factory()->create(['name' => 'Test Warehouse']);
        $location = WarehouseLocation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Test Location'
        ]);
        
        $productBatch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->includePaths('product', 'warehouse', 'warehouseLocation')
            ->get("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'relationships' => [
                    'product' => ['data'],
                    'warehouse' => ['data'],
                    'warehouseLocation' => ['data']
                ]
            ],
            'included' => [
                '*' => [
                    'type',
                    'id',
                    'attributes'
                ]
            ]
        ]);
        
        // Verificar que las relaciones están incluidas
        $included = collect($response->json('included'));
        $this->assertTrue($included->contains('type', 'products'));
        $this->assertTrue($included->contains('type', 'warehouses'));
        $this->assertTrue($included->contains('type', 'warehouse-locations'));
    }

    public function test_product_batch_shows_computed_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.view']);
        
        $productBatch = ProductBatch::factory()->create([
            'initial_quantity' => 100.0000,
            'current_quantity' => 80.0000,
            'reserved_quantity' => 10.0000,
            'unit_cost' => 25.5000,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        
        // Verificar campos computados
        $this->assertEquals(70.0000, $response->json('data.attributes.availableQuantity')); // 80 - 10
        $this->assertEquals(2040.0000, $response->json('data.attributes.totalValue')); // 80 * 25.5
    }

    public function test_unauthorized_user_cannot_view_product_batch()
    {
        $productBatch = ProductBatch::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_view_product_batch()
    {
        $user = $this->createUserWithPermissions('customer', []);
        $productBatch = ProductBatch::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(403);
    }

    public function test_tech_with_view_permission_can_view_product_batch()
    {
        $tech = $this->createUserWithPermissions('tech', ['product-batches.view']);
        $productBatch = ProductBatch::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(200);
        $this->assertEquals($productBatch->id, $response->json('data.id'));
    }

    public function test_returns_404_for_nonexistent_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.view']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches/99999');

        $response->assertStatus(404);
    }
}
