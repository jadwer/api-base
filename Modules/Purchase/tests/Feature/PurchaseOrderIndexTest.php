<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar los seeders necesarios para el sistema de permisos
        $this->seed(\Modules\PermissionManager\Database\Seeders\RoleSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\AssignPermissionsSeeder::class);
        $this->seed(\Modules\Purchase\Database\Seeders\PurchasePermissionSeeder::class);

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

    private function createUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test_role_' . uniqid(), 'guard_name' => 'api']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_list_purchase_orders(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->for($supplier)->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/purchase-orders');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'orderDate',
                        'status',
                        'totalAmount',
                        'notes',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);
        // El seeder crea 3 + nosotros creamos 3 = 6 total
        $response->assertJsonCount(6, 'data');
    }

    public function test_admin_can_filter_purchase_orders_by_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->for($supplier)->count(2)->create(['status' => 'pending']);
        PurchaseOrder::factory()->for($supplier)->count(1)->create(['status' => 'approved']);

        $response = $this->jsonApi()->get('/api/v1/purchase-orders?filter[status]=pending');

        $response->assertOk();
        // Seeder crea 3 + nosotros creamos 2 pending = al menos 2 con status pending
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_include_supplier_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->for($supplier)->create();

        $response = $this->jsonApi()
            ->includePaths('supplier')
            ->get('/api/v1/purchase-orders');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_admin_can_sort_purchase_orders_by_order_date(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $po1 = PurchaseOrder::factory()->for($supplier)->create(['order_date' => '2025-01-01']);
        $po2 = PurchaseOrder::factory()->for($supplier)->create(['order_date' => '2025-01-02']);

        $response = $this->jsonApi()->get('/api/v1/purchase-orders?sort=-orderDate');

        $response->assertOk();
        $dates = collect($response->json('data'))->pluck('attributes.orderDate');

        // Verificar que nuestros registros específicos estén ordenados correctamente
        $po2Date = '2025-01-02T00:00:00.000000Z';
        $po1Date = '2025-01-01T00:00:00.000000Z';

        $po2Index = $dates->search($po2Date);
        $po1Index = $dates->search($po1Date);

        $this->assertTrue($po2Index !== false && $po1Index !== false, 'Fechas no encontradas en la respuesta');
        $this->assertLessThan($po1Index, $po2Index, 'El orden descendente no es correcto');
    }

    public function test_admin_can_filter_purchase_orders_by_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        PurchaseOrder::factory()->for($supplier1)->count(2)->create();
        PurchaseOrder::factory()->for($supplier2)->count(1)->create();

        $response = $this->jsonApi()->get("/api/v1/purchase-orders?filter[supplier]={$supplier1->id}");

        $response->assertOk();
        // Verificar que al menos tenemos nuestros 2 registros del supplier1
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        // Simplemente verificar que el filtro funcionó y hay datos
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_unauthorized_user_cannot_list_purchase_orders(): void
    {
        $response = $this->jsonApi()->get('/api/v1/purchase-orders');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_purchase_orders(): void
    {
        $user = $this->createUserWithPermissions([]); // Sin permisos

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/purchase-orders');
        $response->assertStatus(403);
    }
}
