<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\Supplier;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderItemDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar solo los seeders específicos que necesitamos
        $this->seed(\Modules\PermissionManager\Database\Seeders\RoleSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\AssignPermissionsSeeder::class);
        $this->seed(\Modules\Purchase\Database\Seeders\PurchaseOrderItemPermissionSeeder::class);
        
        // Crear manualmente el usuario admin para evitar conflictos
        $this->createAdminUser();
    }
    
    private function createAdminUser(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => 'secureadmin',
                'status' => 'active',
            ]
        );
        
        // Asignar rol admin si no lo tiene (solo para guard api)
        if (!$admin->hasRole('admin', 'api')) {
            $admin->assignRole('admin');
        }
        
        return $admin;
    }

    private function createUserWithPermissions(string $roleName = 'test_role', array $permissions = []): User
    {
        $user = User::factory()->create();

        $role = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => $roleName . '_' . uniqid(),
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

    private function createPurchaseOrderItem(): PurchaseOrderItem
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id
        ]);
        
        return PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id
        ]);
    }

    public function test_admin_can_delete_purchase_order_item()
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.destroy']);
        $item = $this->createPurchaseOrderItem();
        $itemId = $item->id;

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$itemId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $itemId,
        ]);
    }

    public function test_returns_404_when_deleting_non_existent_purchase_order_item()
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.destroy']);
        $nonExistentId = 99999;

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$nonExistentId}");

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_delete_purchase_order_item()
    {
        $item = $this->createPurchaseOrderItem();
        
        $response = $this->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$item->id}");
        $response->assertStatus(401);
        
        // Verificar que el item no fue eliminado
        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $item->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_purchase_order_item()
    {
        $user = $this->createUserWithPermissions('test', []); // Sin permisos
        $item = $this->createPurchaseOrderItem();
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$item->id}");
            
        $response->assertStatus(403);
        
        // Verificar que el item no fue eliminado
        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_purchase_order_item_preserves_related_data()
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.destroy']);
        $item = $this->createPurchaseOrderItem();
        $purchaseOrderId = $item->purchase_order_id;
        $productId = $item->product_id;

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/purchase-order-items/{$item->id}");

        $response->assertStatus(204);
        
        // Verificar que el item fue eliminado pero las entidades relacionadas se preservaron
        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $item->id,
        ]);
        
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrderId,
        ]);
        
        $this->assertDatabaseHas('products', [
            'id' => $productId,
        ]);
    }

    public function test_admin_can_delete_multiple_items_from_same_purchase_order()
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.destroy']);
        
        // Crear una orden de compra con múltiples items
        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id
        ]);
        
        $item1 = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product1->id
        ]);
        
        $item2 = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product2->id
        ]);

        // Eliminar el primer item
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$item1->id}");

        $response1->assertStatus(204);
        
        // Eliminar el segundo item
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$item2->id}");

        $response2->assertStatus(204);
        
        // Verificar que ambos items fueron eliminados
        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $item1->id,
        ]);
        
        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $item2->id,
        ]);
        
        // Verificar que la orden de compra se mantiene
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }

    public function test_tech_user_cannot_delete_purchase_order_item()
    {
        $tech = $this->createUserWithPermissions('test', ['purchase-order-items.view']); // Solo lectura
        $item = $this->createPurchaseOrderItem();
        
        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$item->id}");
            
        $response->assertStatus(403);
        
        // Verificar que el item no fue eliminado
        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $item->id,
        ]);
    }

    public function test_cannot_delete_same_purchase_order_item_twice()
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.destroy']);
        $item = $this->createPurchaseOrderItem();
        $itemId = $item->id;

        // Primera eliminación - debe ser exitosa
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$itemId}");

        $response1->assertStatus(204);
        
        // Segunda eliminación del mismo item - debe retornar 404
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->delete("/api/v1/purchase-order-items/{$itemId}");

        $response2->assertStatus(404);
    }
}
