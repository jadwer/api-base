<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesOrderDestroyTest extends TestCase
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

    public function test_admin_can_delete_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-DELETE-001',
            'status' => 'draft'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204); // No Content
        
        // Verificar que se eliminó de la base de datos
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_admin_can_delete_draft_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->draft()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-DRAFT-DELETE'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_admin_can_delete_cancelled_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'cancelled'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_tech_user_can_delete_sales_order(): void
    {
        $tech = $this->getTechUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_customer_user_cannot_delete_sales_order(): void
    {
        $customer = $this->getCustomerUser();
        $customerModel = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customerModel->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        // Customer users cannot delete sales orders
        $response->assertStatus(403);
        
        // Verificar que NO se eliminó
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_guest_cannot_delete_sales_order(): void
    {
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $response = $this->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(401);
        
        // Verificar que NO se eliminó
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_cannot_delete_nonexistent_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete('/api/v1/sales-orders/999999');

        $response->assertStatus(404);
    }

    public function test_deleting_sales_order_preserves_customer(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create(['name' => 'Customer Should Remain']);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        
        // Sales order eliminado
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
        
        // Customer debe permanecer
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Customer Should Remain'
        ]);
    }

    public function test_can_delete_sales_order_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'metadata' => [
                'priority' => 'high',
                'source' => 'website',
                'tags' => ['urgent', 'special']
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sales_orders', [
            'id' => $salesOrder->id
        ]);
    }

    public function test_deletion_response_has_no_content(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->delete("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(204);
        $response->assertNoContent();
        
        // Verificar headers
        $this->assertEmpty($response->getContent());
    }
}
