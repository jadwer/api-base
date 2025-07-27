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

class ProductBatchDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'product-batches.destroy', 'guard_name' => 'api']);
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

    public function test_admin_can_delete_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $productBatch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'batch_number' => 'DELETE001',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        // Verificar que se eliminó de la base de datos
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_admin_can_delete_expired_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->expired()->create([
            'batch_number' => 'EXPIRED001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_admin_can_delete_consumed_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->consumed()->create([
            'batch_number' => 'CONSUMED001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_admin_can_delete_quarantine_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->quarantine()->create([
            'batch_number' => 'QUARANTINE001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_admin_can_delete_batch_with_zero_current_quantity()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->create([
            'batch_number' => 'ZERO001',
            'initial_quantity' => 100.0000,
            'current_quantity' => 0.0000,
            'reserved_quantity' => 0.0000,
            'status' => 'consumed'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_admin_can_delete_batch_with_positive_quantity()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->create([
            'batch_number' => 'POSITIVE001',
            'current_quantity' => 50.0000,
            'reserved_quantity' => 10.0000,
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_delete_preserves_related_records()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
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
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        // Verificar que las entidades relacionadas siguen existiendo
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
        $this->assertDatabaseHas('warehouse_locations', ['id' => $location->id]);
    }

    public function test_unauthorized_user_cannot_delete_product_batch()
    {
        $productBatch = ProductBatch::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(401);
        
        // Verificar que no se eliminó
        $this->assertDatabaseHas('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_user_without_permission_cannot_delete_product_batch()
    {
        $user = $this->createUserWithPermissions('customer', []);
        $productBatch = ProductBatch::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");
            
        $response->assertStatus(403);
        
        // Verificar que no se eliminó
        $this->assertDatabaseHas('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_tech_with_destroy_permission_can_delete_product_batch()
    {
        $tech = $this->createUserWithPermissions('tech', ['product-batches.destroy']);
        $productBatch = ProductBatch::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatch->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('product_batches', [
            'id' => $productBatch->id
        ]);
    }

    public function test_returns_404_for_nonexistent_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete('/api/v1/product-batches/99999');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_already_deleted_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.destroy']);
        
        $productBatch = ProductBatch::factory()->create();
        $productBatchId = $productBatch->id;
        
        // Eliminar el batch primero
        $productBatch->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->delete("/api/v1/product-batches/{$productBatchId}");

        $response->assertStatus(404);
    }
}
