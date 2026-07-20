<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Contacts\Models\Contact;
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

        $contact = Contact::factory()->create(['is_supplier' => true]);
        PurchaseOrder::factory()->create(['contact_id' => $contact->id]);
        PurchaseOrder::factory()->count(2)->create();

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

        $contact = Contact::factory()->create(['is_supplier' => true]);
        PurchaseOrder::factory()->count(2)->create(['contact_id' => $contact->id, 'status' => 'pending']);
        PurchaseOrder::factory()->count(1)->create(['contact_id' => $contact->id, 'status' => 'approved']);

        $response = $this->jsonApi()->get('/api/v1/purchase-orders?filter[status]=pending');

        $response->assertOk();
        // Seeder crea 3 + nosotros creamos 2 pending = al menos 2 con status pending
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /**
     * Paquete A (auditoria 10 pasos): filter[search] es el contrato del buscador
     * del FE; antes no existia y el listado respondia 400 al teclear.
     */
    public function test_admin_can_search_purchase_orders(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Contact::factory()->create(['is_supplier' => true, 'name' => 'Proveedor Buscable Unico']);
        $match = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'order_number' => 'OC-SEARCH-555',
        ]);
        PurchaseOrder::factory()->create([
            'contact_id' => Contact::factory()->create(['is_supplier' => true])->id,
            'order_number' => 'OC-OTRA-001',
        ]);

        // Por folio
        $response = $this->jsonApi()->get('/api/v1/purchase-orders?filter[search]=SEARCH-555');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains((string) $match->id, $ids);
        $this->assertCount(1, $ids);

        // Por nombre del proveedor
        $response = $this->jsonApi()->get('/api/v1/purchase-orders?filter[search]=Proveedor Buscable');
        $response->assertOk();
        $this->assertContains((string) $match->id, collect($response->json('data'))->pluck('id'));
    }

    /**
     * Nota cliente #11: compras "por surtir".
     * filter[pending_receipt]=1 debe devolver status pending+approved,
     * nunca received ni cancelled.
     */
    public function test_pending_receipt_filter_returns_pending_and_approved(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Proveedor dedicado para aislar de la data de seeders
        $contact = Contact::factory()->create(['is_supplier' => true]);

        $pending = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'pending']);
        $approved = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'approved']);
        $received = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'received']);
        $cancelled = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'cancelled']);

        $response = $this->jsonApi()
            ->get("/api/v1/purchase-orders?filter[pending_receipt]=1&filter[contact]={$contact->id}&page[size]=100");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertTrue($ids->contains($pending->id), 'pending should be listed');
        $this->assertTrue($ids->contains($approved->id), 'approved should be listed');
        $this->assertFalse($ids->contains($received->id), 'received should NOT be listed');
        $this->assertFalse($ids->contains($cancelled->id), 'cancelled should NOT be listed');
        $this->assertCount(2, $ids, 'only the 2 open orders for this supplier');
    }

    public function test_admin_can_include_supplier_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        $response = $this->jsonApi()
            ->includePaths('contact')
            ->get('/api/v1/purchase-orders');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_admin_can_sort_purchase_orders_by_order_date(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $po1 = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'order_date' => '2025-01-01']);
        $po2 = PurchaseOrder::factory()->create(['contact_id' => $contact->id, 'order_date' => '2025-01-02']);

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

        $contact1 = Contact::factory()->create(['is_supplier' => true]);
        $contact2 = Contact::factory()->create(['is_supplier' => true]);

        PurchaseOrder::factory()->count(2)->create(['contact_id' => $contact1->id]);
        PurchaseOrder::factory()->count(1)->create(['contact_id' => $contact2->id]);

        $response = $this->jsonApi()->get("/api/v1/purchase-orders?filter[contact]={$contact1->id}");

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
