<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\Customer;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerIndexTest extends TestCase
{
    use RefreshDatabase;

    protected static bool $seedersRun = false;

    protected function setUp(): void
    {
        parent::setUp();
        
    }

    private function getUserByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_admin_can_list_customers()
    {
        // Ejecutar seeders solo si no existen usuarios
        if (!User::where('email', 'admin@example.com')->exists()) {
            $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);
        }
        
        $admin = $this->getUserByEmail('admin@example.com');
        
        // Debug: verificar que el usuario existe y tiene permisos
        $this->assertNotNull($admin);
        dump("User found: " . $admin->email);
        dump("User has customers.index permission: " . ($admin->can('customers.index', 'api') ? 'YES' : 'NO'));
        
        // Crear customers de prueba
        $customer1 = Customer::factory()->create([
            'name' => 'Cliente Mayorista',
            'classification' => 'mayorista',
            'credit_limit' => 50000.00,
            'is_active' => true
        ]);
        
        $customer2 = Customer::factory()->create([
            'name' => 'Cliente Minorista',
            'classification' => 'minorista',
            'credit_limit' => 10000.00,
            'is_active' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
            ]
        ]);
    }

    public function test_admin_can_sort_customers_by_name()
    {
        $admin = $this->getUserByEmail('admin@example.com');
        
        // Crear customers con diferentes nombres
        $customer1 = Customer::factory()->create(['name' => 'Carlos Lopez']);
        $customer2 = Customer::factory()->create(['name' => 'Ana Martinez']);
        $customer3 = Customer::factory()->create(['name' => 'Bruno Sanchez']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers?sort=name');

        $response->assertStatus(200);
        
        $names = collect($response->json('data'))
            ->pluck('attributes.name');
            
        $this->assertEquals(['Ana Martinez', 'Bruno Sanchez', 'Carlos Lopez'], $names->toArray());
    }

    public function test_admin_can_filter_customers_by_classification()
    {
        $admin = $this->getUserByEmail('admin@example.com');
        
        // Crear customers con diferentes clasificaciones
        $mayorista = Customer::factory()->create(['classification' => 'mayorista']);
        $minorista = Customer::factory()->create(['classification' => 'minorista']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers?filter[classification]=mayorista');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        
        $this->assertEquals(
            'mayorista',
            $response->json('data.0.attributes.classification')
        );
    }

    public function test_admin_can_filter_customers_by_active_status()
    {
        $admin = $this->getUserByEmail('admin@example.com');
        
        // Crear customers activos e inactivos
        $activeCustomer = Customer::factory()->create(['is_active' => true]);
        $inactiveCustomer = Customer::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers?filter[is_active]=true');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        
        $this->assertTrue(
            $response->json('data.0.attributes.isActive')
        );
    }

    public function test_tech_user_can_list_customers_with_permission()
    {
        $tech = $this->getUserByEmail('tech@example.com');
        
        Customer::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers');

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_list_customers()
    {
        $user = $this->getUserByEmail('customer@example.com');
        
        Customer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_customers()
    {
        Customer::factory()->create();

        $response = $this->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers');

        $response->assertStatus(401);
    }

    public function test_can_paginate_customers()
    {
        $admin = $this->getUserByEmail('admin@example.com');
        
        // Crear 25 customers
        Customer::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers?page[size]=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        
        // Verificar metadatos de paginación
        $response->assertJsonStructure([
            'meta' => [
                'page' => [
                    'currentPage',
                    'perPage',
                    'total'
                ]
            ],
            'links' => [
                'first',
                'last',
                'next'
            ]
        ]);
    }

    public function test_can_search_customers_by_name()
    {
        $admin = $this->getUserByEmail('admin@example.com');
        
        Customer::factory()->create(['name' => 'Juan Perez']);
        Customer::factory()->create(['name' => 'Maria Rodriguez']);
        Customer::factory()->create(['name' => 'Carlos Martinez']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers?filter[name]=Juan');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        
        $this->assertStringContainsString(
            'Juan',
            $response->json('data.0.attributes.name')
        );
    }
}
