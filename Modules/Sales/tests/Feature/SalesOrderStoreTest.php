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
                'discountTotal' => 50.00,
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);

        // Verificar datos específicos
        $this->assertEquals('SO-NEW-001', $response->json('data.attributes.orderNumber'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        // Totales del servidor (22-10): el totalAmount del cliente se ignora; sin partidas es 0
        $this->assertEquals(0, $response->json('data.attributes.totalAmount'));
        $this->assertEquals('Test sales order creation', $response->json('data.attributes.notes'));

        // Verificar que se guardó en base de datos
        $this->assertDatabaseHas('sales_orders', [
            'order_number' => 'SO-NEW-001',
            'contact_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 0
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
        $this->assertEquals(0, $response->json('data.attributes.totalAmount'));
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
                'totalAmount' => 550.00
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData($data)
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        $this->assertEquals('SO-TECH-001', $response->json('data.attributes.orderNumber'));
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

    public function test_client_totals_are_ignored(): void
    {
        // Hallazgo Atomo 22-10: el navegador mandaba los totales y nadie los
        // verificaba; ahora son de solo lectura y los calcula el servidor.
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData([
                'type' => 'sales-orders',
                'attributes' => [
                    'contactId' => $customer->id,
                    'orderNumber' => 'SO-CLIENT-TOTALS',
                    'status' => 'draft',
                    'orderDate' => '2024-01-15',
                    'totalAmount' => 99999,
                    'taxAmount' => 0,
                ],
            ])
            ->post('/api/v1/sales-orders');

        $response->assertCreated();
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'SO-CLIENT-TOTALS', 'total_amount' => 0]);
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
