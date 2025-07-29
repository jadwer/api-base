<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;

class CustomerDestroyTest extends TestCase
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

    public function test_admin_can_delete_customer_without_orders(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Customer to Delete',
            'email' => 'delete@example.com'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204); // No Content para DELETE exitoso
        
        // Verificar que se eliminó de la BD
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_tech_user_can_delete_customer_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Tech Delete Customer'
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_customer_user_cannot_delete_other_customers(): void
    {
        $customer_user = $this->getCustomerUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Protected Customer'
        ]);

        $response = $this->actingAs($customer_user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertForbidden();
        
        // Verificar que NO se eliminó
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_guest_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Guest Protected Customer'
        ]);

        $response = $this->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(401);
        
        // Verificar que NO se eliminó
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_cannot_delete_nonexistent_customer(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete('/api/v1/customers/99999');

        $response->assertNotFound();
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Empty Response Customer'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        // Verificar que la respuesta está vacía (como debe ser en DELETE)
        $this->assertEmpty($response->getContent());
    }

    public function test_can_delete_inactive_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Inactive Customer to Delete',
            'is_active' => false
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_can_delete_customer_with_high_credit_limit(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'High Credit Customer',
            'credit_limit' => 100000.00
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_can_delete_customer_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Metadata Customer',
            'metadata' => [
                'source' => 'web',
                'preferences' => ['payment_method' => 'credit'],
                'notes' => 'Important customer'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create([
            'name' => 'Idempotent Delete Customer'
        ]);

        // Primera eliminación
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response1->assertStatus(204);
        
        // Segunda eliminación (debería devolver 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response2->assertNotFound();
    }
}
