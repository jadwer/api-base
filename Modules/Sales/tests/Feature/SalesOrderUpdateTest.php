<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesOrderUpdateTest extends TestCase
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

    public function test_admin_can_update_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-UPDATE-001',
            'status' => 'draft',
            'total_amount' => 1000.00
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'order_number' => 'SO-UPDATED-001',
                'status' => 'confirmed',
                'total_amount' => 1500.00,
                'notes' => 'Updated order notes'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        // Verificar que se actualizó en base de datos
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'order_number' => 'SO-UPDATED-001',
            'status' => 'confirmed',
            'total_amount' => 1500.00,
            'notes' => 'Updated order notes'
        ]);

        // Verificar respuesta
        $this->assertEquals('SO-UPDATED-001', $response->json('data.attributes.order_number'));
        $this->assertEquals('confirmed', $response->json('data.attributes.status'));
        $this->assertEquals(1500.00, $response->json('data.attributes.total_amount'));
    }

    public function test_admin_can_update_sales_order_status(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'draft'
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'status' => 'confirmed'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        $this->assertEquals('confirmed', $response->json('data.attributes.status'));
        
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'status' => 'confirmed'
        ]);
    }

    public function test_admin_can_update_sales_order_metadata(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'metadata' => ['priority' => 'low']
        ]);

        $newMetadata = [
            'priority' => 'high',
            'source' => 'website',
            'special_instructions' => 'Handle with care'
        ];

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'metadata' => $newMetadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        $responseMetadata = $response->json('data.attributes.metadata');
        $this->assertEquals($newMetadata, $responseMetadata);
        $this->assertEquals('high', $responseMetadata['priority']);
        $this->assertEquals('website', $responseMetadata['source']);
    }

    public function test_tech_user_can_update_sales_order(): void
    {
        $tech = $this->getTechUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-TECH-UPDATE'
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'notes' => 'Updated by tech user'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        $this->assertEquals('Updated by tech user', $response->json('data.attributes.notes'));
    }

    public function test_customer_user_cannot_update_sales_order(): void
    {
        $customer = $this->getCustomerUser();
        $customerModel = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customerModel->id
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'notes' => 'Customer trying to update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        // Customer users cannot update sales orders
        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_sales_order(): void
    {
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'notes' => 'Guest trying to update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-orders',
            'id' => '999999',
            'attributes' => [
                'notes' => 'Trying to update non-existent order'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch('/api/v1/sales-orders/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_status(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $statusError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'status');
        });
        $this->assertNotNull($statusError, 'Expected status validation error');
    }

    public function test_cannot_update_order_number_to_duplicate(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        
        // Crear dos sales orders
        $salesOrder1 = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-EXISTING-001'
        ]);
        $salesOrder2 = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-TO-UPDATE-001'
        ]);

        // Intentar actualizar el segundo order con el número del primero
        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder2->id,
            'attributes' => [
                'order_number' => 'SO-EXISTING-001'  // Duplicado
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder2->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $orderNumberError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'order_number');
        });
        $this->assertNotNull($orderNumberError, 'Expected order_number unique validation error');
    }

    public function test_can_update_order_number_to_same_value(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-SAME-001'
        ]);

        // Actualizar el order number al mismo valor (debería permitirse)
        $data = [
            'type' => 'sales-orders',
            'id' => (string) $salesOrder->id,
            'attributes' => [
                'order_number' => 'SO-SAME-001',  // Mismo valor
                'notes' => 'Updated notes'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->patch("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        $this->assertEquals('SO-SAME-001', $response->json('data.attributes.order_number'));
        $this->assertEquals('Updated notes', $response->json('data.attributes.notes'));
    }
}
