<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class CustomerShowTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_view_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Test Customer Show',
            'email' => 'test.show@example.com',
            'classification' => 'mayorista',
            'credit_limit' => 50000.00,
            'current_credit' => 15000.00,
            'is_active' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        
        // Verificar estructura básica
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'classification',
                    'credit_limit',
                    'current_credit',
                    'is_active',
                ]
            ]
        ]);

        // Verificar datos específicos
        $this->assertEquals('Test Customer Show', $response->json('data.attributes.name'));
        $this->assertEquals('test.show@example.com', $response->json('data.attributes.email'));
        $this->assertEquals('mayorista', $response->json('data.attributes.classification'));
        $this->assertEquals(50000.00, $response->json('data.attributes.credit_limit'));
        $this->assertEquals(15000.00, $response->json('data.attributes.current_credit'));
        $this->assertTrue($response->json('data.attributes.is_active'));
    }

    public function test_admin_can_view_customer_with_relationships(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create(['name' => 'Customer with Relations']);
        
        // Crear algunos sales orders para probar la relación
        SalesOrder::factory()->count(2)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->includePaths('salesOrders')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertEquals('Customer with Relations', $response->json('data.attributes.name'));
        
        // Verificar que se incluyen las relaciones
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'salesOrders' => ['data']
                ]
            ],
            'included'
        ]);
        
        // Verificar que los sales orders están en included
        $included = $response->json('included');
        $this->assertCount(2, $included);
        $this->assertEquals('sales-orders', $included[0]['type']);
        $this->assertEquals('sales-orders', $included[1]['type']);
    }

    public function test_admin_can_view_inactive_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Inactive Customer',
            'is_active' => false
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.is_active'));
        $this->assertEquals('Inactive Customer', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_view_customer_with_permission(): void
    {
        $tech = $this->getTechUser();
        $customer = Customer::factory()->create(['name' => 'Tech Viewable Customer']);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertEquals('Tech Viewable Customer', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_view_other_customers(): void
    {
        $customer_user = $this->getCustomerUser();
        $customer = Customer::factory()->create(['name' => 'Other Customer']);

        $response = $this->actingAs($customer_user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Guest Customer']);

        $response = $this->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_view_nonexistent_customer(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get('/api/v1/customers/99999');

        $response->assertNotFound();
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create(['name' => 'Timestamp Customer']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertNotNull($response->json('data.attributes.created_at'));
        $this->assertNotNull($response->json('data.attributes.updated_at'));
    }

    public function test_metadata_is_properly_formatted(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Metadata Customer',
            'metadata' => [
                'source' => 'web',
                'preferences' => ['payment_method' => 'credit']
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->get("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertIsArray($response->json('data.attributes.metadata'));
        $this->assertEquals('web', $response->json('data.attributes.metadata.source'));
    }
}
