<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;

class CustomerUpdateTest extends TestCase
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

    public function test_admin_can_update_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Original Customer',
            'email' => 'original@example.com',
            'classification' => 'minorista',
            'credit_limit' => 10000.00,
            'is_active' => true
        ]);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'name' => 'Updated Customer Name',
                'classification' => 'mayorista',
                'credit_limit' => 50000.00,
                'current_credit' => 15000.00,
                'is_active' => false,
                'metadata' => [
                    'source' => 'updated_web',
                    'notes' => 'Customer updated via API'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        
        // Verificar que se actualizó en BD
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer Name',
            'classification' => 'mayorista',
            'credit_limit' => 50000.00,
            'is_active' => false
        ]);

        // Verificar respuesta JSON
        $this->assertEquals('Updated Customer Name', $response->json('data.attributes.name'));
        $this->assertEquals('mayorista', $response->json('data.attributes.classification'));
        $this->assertEquals(50000.00, $response->json('data.attributes.credit_limit'));
        $this->assertFalse($response->json('data.attributes.is_active'));
    }

    public function test_admin_can_partially_update_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Partial Update Customer',
            'email' => 'partial@example.com',
            'classification' => 'minorista',
            'credit_limit' => 20000.00
        ]);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'credit_limit' => 35000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        
        // Verificar que solo se actualizó credit_limit
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Partial Update Customer', // Sin cambios
            'email' => 'partial@example.com',    // Sin cambios
            'classification' => 'minorista',      // Sin cambios
            'credit_limit' => 35000.00           // Actualizado
        ]);

        $this->assertEquals(35000.00, $response->json('data.attributes.credit_limit'));
        $this->assertEquals('Partial Update Customer', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_update_customer_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Tech Update Customer',
            'email' => 'tech.update@example.com'
        ]);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'name' => 'Tech Updated Name'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertEquals('Tech Updated Name', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_update_other_customers(): void
    {
        $customer_user = $this->getCustomerUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Other Customer to Update'
        ]);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer_user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_update_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Guest Update Customer']);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'name' => 'Guest Updated Name'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_customer_with_duplicate_email(): void
    {
        $admin = $this->getAdminUser();
        
        // Crear dos customers
        $customer1 = Customer::factory()->create(['email' => 'existing@example.com']);
        $customer2 = Customer::factory()->create(['email' => 'toupdate@example.com']);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer2->id,
            'attributes' => [
                'email' => 'existing@example.com' // Email ya existente
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer2->id}");

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

    public function test_can_update_customer_with_same_email(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Same Email Customer',
            'email' => 'same@example.com'
        ]);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'name' => 'Updated Name',
                'email' => 'same@example.com' // Mismo email, debería permitirse
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertOk();
        $this->assertEquals('Updated Name', $response->json('data.attributes.name'));
        $this->assertEquals('same@example.com', $response->json('data.attributes.email'));
    }

    public function test_cannot_update_customer_with_invalid_classification(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create(['name' => 'Invalid Class Customer']);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'classification' => 'invalid_classification'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertStatus(422);
        
        // Verificar error de classification inválida
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'classification')
            )
        );
    }

    public function test_cannot_update_customer_with_negative_credit_limit(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create(['name' => 'Negative Credit Customer']);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'credit_limit' => -5000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

        $response->assertStatus(422);
        
        // Verificar error de credit_limit negativo
        $errors = $response->json('errors');
        $this->assertTrue(
            collect($errors)->contains(fn($e) => 
                str_contains($e['source']['pointer'], 'credit_limit')
            )
        );
    }

    public function test_cannot_update_customer_with_invalid_email(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create(['name' => 'Invalid Email Customer']);

        $updateData = [
            'type' => 'customers',
            'id' => (string) $customer->id,
            'attributes' => [
                'email' => 'invalid-email-format'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch("/api/v1/customers/{$customer->id}");

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

    public function test_cannot_update_nonexistent_customer(): void
    {
        $admin = $this->getAdminUser();

        $updateData = [
            'type' => 'customers',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Nonexistent'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->withData($updateData)
            ->patch('/api/v1/customers/99999');

        $response->assertNotFound();
    }
}
