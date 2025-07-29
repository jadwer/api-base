<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesOrderStoreTest extends TestCase
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

    public function test_admin_can_create_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-NEW-001',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'subtotal_amount' => 1000.00,
                'tax_amount' => 100.00,
                'discount_total' => 50.00,
                'total_amount' => 1050.00,
                'notes' => 'Test sales order creation',
                'metadata' => [
                    'priority' => 'high',
                    'source' => 'web'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'order_number',
                    'status',
                    'total_amount',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        // Verificar datos específicos
        $this->assertEquals('SO-NEW-001', $response->json('data.attributes.order_number'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        $this->assertEquals(1050.00, $response->json('data.attributes.total_amount'));
        $this->assertEquals('Test sales order creation', $response->json('data.attributes.notes'));

        // Verificar que se guardó en base de datos
        $this->assertDatabaseHas('sales_orders', [
            'order_number' => 'SO-NEW-001',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 1050.00
        ]);
    }

    public function test_admin_can_create_confirmed_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-CONFIRMED-001',
                'status' => 'confirmed',
                'order_date' => '2024-01-15',
                'subtotal_amount' => 2000.00,
                'tax_amount' => 200.00,
                'total_amount' => 2200.00,
                'notes' => 'Confirmed order test'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        $this->assertEquals('confirmed', $response->json('data.attributes.status'));
        $this->assertEquals(2200.00, $response->json('data.attributes.total_amount'));
    }

    public function test_tech_user_can_create_sales_order(): void
    {
        $tech = $this->getTechUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-TECH-001',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'subtotal_amount' => 500.00,
                'tax_amount' => 50.00,
                'total_amount' => 550.00
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        $this->assertEquals('SO-TECH-001', $response->json('data.attributes.order_number'));
    }

    public function test_customer_user_cannot_create_sales_order(): void
    {
        $customer = $this->getCustomerUser();
        $customerModel = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customerModel->id,
                'order_number' => 'SO-CUSTOMER-001',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'total_amount' => 100.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        // Customer users cannot create sales orders
        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_sales_order(): void
    {
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-GUEST-001',
                'status' => 'draft',
                'total_amount' => 100.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertStatus(401);
    }

    public function test_order_number_is_required(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'total_amount' => 100.00
                // order_number missing
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertStatus(422);
        // Para JSON API, los errores están en formato diferente
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $orderNumberError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'order_number');
        });
        $this->assertNotNull($orderNumberError, 'Expected order_number validation error');
    }

    public function test_customer_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'order_number' => 'SO-NO-CUSTOMER',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'total_amount' => 100.00
                // customer_id missing
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $customerIdError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'customer_id');
        });
        $this->assertNotNull($customerIdError, 'Expected customer_id validation error');
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-INVALID-STATUS',
                'status' => 'invalid_status',
                'order_date' => '2024-01-15',
                'total_amount' => 100.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $statusError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'status');
        });
        $this->assertNotNull($statusError, 'Expected status validation error');
    }

    public function test_total_amount_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-INVALID-AMOUNT',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'total_amount' => 'not_a_number'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $totalAmountError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'total_amount');
        });
        $this->assertNotNull($totalAmountError, 'Expected total_amount validation error');
    }

    public function test_metadata_is_stored_correctly(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create();

        $metadata = [
            'priority' => 'high',
            'source' => 'mobile_app',
            'notes' => 'Special customer'
        ];

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'customer_id' => $customer->id,
                'order_number' => 'SO-METADATA-001',
                'status' => 'draft',
                'order_date' => '2024-01-15',
                'total_amount' => 750.00,
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        
        $responseMetadata = $response->json('data.attributes.metadata');
        $this->assertEquals($metadata, $responseMetadata);
        $this->assertEquals('high', $responseMetadata['priority']);
        $this->assertEquals('mobile_app', $responseMetadata['source']);
    }
}
