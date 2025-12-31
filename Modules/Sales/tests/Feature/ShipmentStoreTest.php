<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Tests for Shipment Store endpoint.
 */
class ShipmentStoreTest extends TestCase
{
    public function test_admin_can_create_shipment(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'status' => 'pending',
                    'carrier' => 'FedEx',
                    'shippingAddress' => '123 Main St, City, Country',
                    'shippingCost' => 99.99,
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.carrier', 'FedEx');
        $response->assertJsonPath('data.attributes.status', 'pending');

        $this->assertDatabaseHas('shipments', [
            'sales_order_id' => $order->id,
            'carrier' => 'FedEx',
        ]);
    }

    public function test_admin_can_create_shipment_with_tracking(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'carrier' => 'UPS',
                    'trackingNumber' => 'UPS123456789',
                    'trackingUrl' => 'https://ups.com/track/UPS123456789',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.trackingNumber', 'UPS123456789');
    }

    public function test_tech_user_can_create_shipment(): void
    {
        $tech = $this->getTechUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'carrier' => 'DHL',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertCreated();
    }

    public function test_customer_cannot_create_shipment(): void
    {
        $customerUser = $this->getCustomerUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($customerUser, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'carrier' => 'FedEx',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'carrier' => 'FedEx',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertStatus(401);
    }

    public function test_cannot_create_shipment_without_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'carrier' => 'FedEx',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertStatus(422);
    }

    public function test_cannot_create_shipment_with_invalid_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => 99999,
                    'carrier' => 'FedEx',
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertStatus(422);
    }

    public function test_shipment_number_is_auto_generated(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertCreated();

        $shipmentNumber = $response->json('data.attributes.shipmentNumber');
        $this->assertNotNull($shipmentNumber);
        $this->assertStringStartsWith('SHP-', $shipmentNumber);
    }

    public function test_can_create_shipment_with_metadata(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $metadata = [
            'package_count' => 2,
            'insurance' => true,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'metadata' => $metadata,
                ],
            ])
            ->post('/api/v1/shipments');

        $response->assertCreated();

        $this->assertDatabaseHas('shipments', [
            'sales_order_id' => $order->id,
        ]);
    }
}
