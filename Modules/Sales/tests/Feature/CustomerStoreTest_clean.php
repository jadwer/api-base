<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;

class CustomerStoreTest extends TestCase
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

    public function test_admin_can_create_customer(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'New Customer Store Test',
                'email' => 'newcustomer@example.com',
                'phone' => '+1234567890',
                'address' => 'Test Address 123',
                'city' => 'Test City',
                'state' => 'Test State',
                'country' => 'Test Country',
                'classification' => 'mayorista',
                'credit_limit' => 75000.00,
                'current_credit' => 0.00,
                'is_active' => true,
                'metadata' => [
                    'source' => 'web',
                    'preferences' => ['payment_method' => 'credit']
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertCreated();
        
        // Verificar que se creó en BD
        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer Store Test',
            'email' => 'newcustomer@example.com',
            'classification' => 'mayorista',
            'credit_limit' => 75000.00,
            'is_active' => true
        ]);

        // Verificar respuesta JSON
        $this->assertEquals('New Customer Store Test', $response->json('data.attributes.name'));
        $this->assertEquals('newcustomer@example.com', $response->json('data.attributes.email'));
        $this->assertEquals('mayorista', $response->json('data.attributes.classification'));
        $this->assertEquals(75000.00, $response->json('data.attributes.credit_limit'));
    }

    public function test_admin_can_create_customer_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Minimal Customer',
                'email' => 'minimal@example.com',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertCreated();
        
        // Verificar valores por defecto
        $this->assertEquals('Minimal Customer', $response->json('data.attributes.name'));
        $this->assertEquals('minorista', $response->json('data.attributes.classification'));
        $this->assertTrue($response->json('data.attributes.is_active'));
    }

    public function test_tech_user_can_create_customer_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Tech Customer',
                'email' => 'tech.customer@example.com',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertCreated();
        $this->assertEquals('Tech Customer', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_create_customer(): void
    {
        $customer_user = $this->getCustomerUser();
        
        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Unauthorized Customer',
                'email' => 'unauthorized@example.com',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->actingAs($customer_user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertForbidden();
    }

    public function test_guest_cannot_create_customer(): void
    {
        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Guest Customer',
                'email' => 'guest@example.com',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(401);
    }

    public function test_cannot_create_customer_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'phone' => '+1234567890'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(422);
        
        // Verificar que contiene errores para campos requeridos
        $errors = $response->json('errors');
        $this->assertTrue(collect($errors)->contains(fn($e) => str_contains($e['source']['pointer'], 'name')));
        $this->assertTrue(collect($errors)->contains(fn($e) => str_contains($e['source']['pointer'], 'email')));
        $this->assertTrue(collect($errors)->contains(fn($e) => str_contains($e['source']['pointer'], 'classification')));
    }

    public function test_cannot_create_customer_with_duplicate_email(): void
    {
        $admin = $this->getAdminUser();
        
        // Crear customer existente
        Customer::factory()->create(['email' => 'duplicate@example.com']);

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Duplicate Email Customer',
                'email' => 'duplicate@example.com',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(422);
        
        // Verificar error específico de email duplicado
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'email') && 
                str_contains($e['detail'], 'taken')
            )
        );
    }

    public function test_cannot_create_customer_with_invalid_email(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Invalid Email Customer',
                'email' => 'invalid-email-format',
                'classification' => 'minorista'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(422);
        
        // Verificar error de formato de email
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'email') && 
                str_contains($e['detail'], 'valid email')
            )
        );
    }

    public function test_cannot_create_customer_with_invalid_classification(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Invalid Classification Customer',
                'email' => 'invalidclass@example.com',
                'classification' => 'invalid_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(422);
        
        // Verificar error de classification inválida
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'classification')
            )
        );
    }

    public function test_cannot_create_customer_with_negative_credit_limit(): void
    {
        $admin = $this->getAdminUser();

        $customerData = [
            'type' => 'customers',
            'attributes' => [
                'name' => 'Negative Credit Customer',
                'email' => 'negative@example.com',
                'classification' => 'minorista',
                'credit_limit' => -1000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($customerData)
            ->post('/api/v1/customers');

        $response->assertStatus(422);
        
        // Verificar error de credit_limit negativo
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'credit_limit')
            )
        );
    }
}
