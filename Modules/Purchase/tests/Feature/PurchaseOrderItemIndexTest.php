<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\Supplier;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderItemIndexTest extends TestCase
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

    public function test_admin_can_list_purchase_order_items(): void
    {
        $admin = $this->createUserWithPermissions("admin", ['purchase-order-items.index']);

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
        PurchaseOrderItem::factory()->for($purchaseOrder)->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->get('/api/v1/purchase-order-items');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'purchaseOrderId',
                        'productId',
                        'quantity',
                        'unitPrice',
                        'subtotal',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_filter_purchase_order_items_by_purchase_order(): void
    {
        $admin = $this->createUserWithPermissions("admin", ['purchase-order-items.index']);

        $supplier = Supplier::factory()->create();
        $purchaseOrder1 = PurchaseOrder::factory()->for($supplier)->create();
        $purchaseOrder2 = PurchaseOrder::factory()->for($supplier)->create();
        
        PurchaseOrderItem::factory()->for($purchaseOrder1)->count(2)->create();
        PurchaseOrderItem::factory()->for($purchaseOrder2)->count(1)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->get("/api/v1/purchase-order-items?filter[purchaseOrder]={$purchaseOrder1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_include_purchase_order_data(): void
    {
        $admin = $this->createUserWithPermissions("admin", ['purchase-order-items.index']);

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
        PurchaseOrderItem::factory()->for($purchaseOrder)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->includePaths('purchaseOrder')
            ->get('/api/v1/purchase-order-items');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_admin_can_sort_purchase_order_items_by_quantity(): void
    {
        $admin = $this->createUserWithPermissions("admin", ['purchase-order-items.index']);

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
        $item1 = PurchaseOrderItem::factory()->for($purchaseOrder)->create(['quantity' => 10]);
        $item2 = PurchaseOrderItem::factory()->for($purchaseOrder)->create(['quantity' => 5]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->get('/api/v1/purchase-order-items?sort=quantity');

        $response->assertOk();
        $quantities = collect($response->json('data'))->pluck('attributes.quantity');
        
        // Verificar que nuestros items específicos están ordenados correctamente
        $quantity5Index = $quantities->search(5);
        $quantity10Index = $quantities->search(10);
        
        $this->assertTrue($quantity5Index !== false && $quantity10Index !== false, 'Items de prueba no encontrados');
        $this->assertLessThan($quantity10Index, $quantity5Index, 'El ordenamiento ascendente no es correcto');
    }

    public function test_unauthorized_user_cannot_list_purchase_order_items(): void
    {
        $response = $this->jsonApi()
            ->expects('purchase-order-items')
            ->get('/api/v1/purchase-order-items');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_purchase_order_items(): void
    {
        $user = $this->createUserWithPermissions("admin", []); // Sin permisos

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('purchase-order-items')
            ->get('/api/v1/purchase-order-items');
        $response->assertStatus(403);
    }
}
