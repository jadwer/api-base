<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\Supplier;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderItemUpdateTest extends TestCase
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
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 20.00,
        ]);
    }

    public function test_admin_can_update_purchase_order_item()
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $item = $this->createPurchaseOrderItem();
        
        $data = [
            'data' => [
                'type' => 'purchase-order-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 10,
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->withData($data['data'])
            ->patch("/api/v1/purchase-order-items/{$item->id}");

        $response->assertStatus(200);
        
        // Verificar que los datos se actualizaron correctamente
        $response->assertJson([
            'data' => [
                'type' => 'purchase-order-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 10,
                ]
            ]
        ]);
    }

    public function test_unauthorized_user_cannot_update_purchase_order_item()
    {
        $item = $this->createPurchaseOrderItem();

        $data = [
            'data' => [
                'type' => 'purchase-order-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 10,
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('purchase-order-items')
            ->withData($data['data'])
            ->patch("/api/v1/purchase-order-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_update_purchase_order_item()
    {
        $user = $this->createUserWithPermissions('user', []); // Sin permisos
        $item = $this->createPurchaseOrderItem();

        $data = [
            'data' => [
                'type' => 'purchase-order-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 10,
                ]
            ]
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->withData($data['data'])
            ->patch("/api/v1/purchase-order-items/{$item->id}");

        $response->assertStatus(403);
    }
}
