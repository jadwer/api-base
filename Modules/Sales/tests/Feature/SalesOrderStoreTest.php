<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;

class SalesOrderStoreTest extends TestCase
{



    public function test_admin_can_create_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-NEW-001',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'subtotal_amount' => 1000.00,
                'taxAmount' => 100.00,
                'discount_total' => 50.00,
                'totalAmount' => 1050.00,
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
                    'orderNumber',
                    'status',
                    'totalAmount',
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
            'orderNumber' => 'SO-NEW-001',
            'contactId' => $customer->id,
            'status' => 'draft',
            'totalAmount' => 1050.00
        ]);
    }

    public function test_admin_can_create_confirmed_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-CONFIRMED-001',
                'status' => 'confirmed',
                'orderDate' => '2024-01-15',
                'subtotal_amount' => 2000.00,
                'taxAmount' => 200.00,
                'totalAmount' => 2200.00,
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
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-TECH-001',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'subtotal_amount' => 500.00,
                'taxAmount' => 50.00,
                'totalAmount' => 550.00
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
        $customerModel = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customerModel->id,
                'orderNumber' => 'SO-CUSTOMER-001',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'totalAmount' => 100.00
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
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-GUEST-001',
                'status' => 'draft',
                'totalAmount' => 100.00
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
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'totalAmount' => 100.00
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
            return str_contains($error['source']['pointer'] ?? '', 'orderNumber');
        });
        $this->assertNotNull($orderNumberError, 'Expected order_number validation error');
    }

    public function test_customer_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'orderNumber' => 'SO-NO-CUSTOMER',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'totalAmount' => 100.00
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
        $contactIdError = collect($errors)->first(function ($error) {
            return str_contains($error['source']['pointer'] ?? '', 'contactId');
        });
        $this->assertNotNull($contactIdError, 'Expected contact_id validation error');
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-INVALID-STATUS',
                'status' => 'invalid_status',
                'orderDate' => '2024-01-15',
                'totalAmount' => 100.00
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
        $customer = Contact::factory()->customer()->create();

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-INVALID-AMOUNT',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'totalAmount' => 'not_a_number'
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
            return str_contains($error['source']['pointer'] ?? '', 'totalAmount');
        });
        $this->assertNotNull($totalAmountError, 'Expected total_amount validation error');
    }

    public function test_metadata_is_stored_correctly(): void
    {
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create();

        $metadata = [
            'priority' => 'high',
            'source' => 'mobile_app',
            'notes' => 'Special customer'
        ];

        $data = [
            'type' => 'sales-orders',
            'attributes' => [
                'contactId' => $customer->id,
                'orderNumber' => 'SO-METADATA-001',
                'status' => 'draft',
                'orderDate' => '2024-01-15',
                'totalAmount' => 750.00,
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
