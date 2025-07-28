<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\Customer;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'customers.view', 'guard_name' => 'api']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role, 'api');
        
        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_view_customer()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);
        
        $customer = Customer::factory()->create([
            'name' => 'Cliente Test S.A.',
            'email' => 'cliente@test.com',
            'phone' => '+1234567890',
            'classification' => 'mayorista',
            'credit_limit' => 50000.00,
            'current_credit' => 15000.00,
            'is_active' => true,
            'metadata' => [
                'source' => 'web',
                'preferences' => ['payment_method' => 'credit']
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'phone',
                    'address',
                    'city',
                    'state',
                    'country',
                    'classification',
                    'creditLimit',
                    'currentCredit',
                    'isActive',
                    'metadata'
                ]
            ]
        ]);
        
        // Verificar valores específicos
        $this->assertEquals($customer->id, $response->json('data.id'));
        $this->assertEquals('customers', $response->json('data.type'));
        $this->assertEquals('Cliente Test S.A.', $response->json('data.attributes.name'));
        $this->assertEquals('cliente@test.com', $response->json('data.attributes.email'));
        $this->assertEquals('mayorista', $response->json('data.attributes.classification'));
        $this->assertEquals(50000.00, $response->json('data.attributes.creditLimit'));
        $this->assertEquals(15000.00, $response->json('data.attributes.currentCredit'));
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_admin_can_view_customer_with_relationships()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}?include=salesOrders");

        $response->assertStatus(200);
        
        // Verificar que incluye la estructura de relaciones
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'salesOrders' => [
                        'data'
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_view_inactive_customer()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);
        
        $customer = Customer::factory()->create([
            'is_active' => false,
            'name' => 'Cliente Inactivo'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.attributes.isActive'));
        $this->assertEquals('Cliente Inactivo', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_view_customer_with_permission()
    {
        $tech = $this->createUserWithPermissions('tech', ['customers.view']);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_view_customer()
    {
        $user = $this->createUserWithPermissions('customer', []);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_view_nonexistent_customer()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }

    public function test_metadata_is_properly_formatted()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.view']);
        
        $metadata = [
            'source' => 'mobile_app',
            'preferences' => [
                'payment_method' => 'bank_transfer',
                'delivery_preference' => 'morning'
            ],
            'notes' => 'Cliente VIP'
        ];
        
        $customer = Customer::factory()->create(['metadata' => $metadata]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
        
        $responseMetadata = $response->json('data.attributes.metadata');
        $this->assertEquals($metadata, $responseMetadata);
        $this->assertEquals('mobile_app', $responseMetadata['source']);
        $this->assertEquals('bank_transfer', $responseMetadata['preferences']['payment_method']);
    }
}
