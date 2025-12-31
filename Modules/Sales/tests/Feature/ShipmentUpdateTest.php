<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Tests for Shipment Update endpoint.
 */
class ShipmentUpdateTest extends TestCase
{
    public function test_admin_can_update_shipment(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'carrier' => 'Updated Carrier',
                    'trackingNumber' => 'TRACK12345',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.carrier', 'Updated Carrier');
        $response->assertJsonPath('data.attributes.trackingNumber', 'TRACK12345');
    }

    public function test_admin_can_update_shipment_status(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'status' => 'processing',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'processing');
    }

    public function test_tech_user_can_update_shipment(): void
    {
        $tech = $this->getTechUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'notes' => 'Updated notes',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
    }

    public function test_customer_cannot_update_shipment(): void
    {
        $customerUser = $this->getCustomerUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($customerUser, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'carrier' => 'New Carrier',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'carrier' => 'New Carrier',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_with_invalid_status(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'status' => 'invalid_status',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(422);
    }

    public function test_can_update_shipment_shipping_cost(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create([
            'sales_order_id' => $order->id,
            'shipping_cost' => 50.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'shippingCost' => 75.50,
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.shippingCost', 75.50);
    }

    public function test_can_update_shipment_dates(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => (string) $shipment->id,
                'attributes' => [
                    'shipDate' => '2025-01-15',
                    'estimatedDelivery' => '2025-01-20',
                ],
            ])
            ->patch("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
    }

    public function test_updating_nonexistent_shipment_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->withData([
                'type' => 'shipments',
                'id' => '99999',
                'attributes' => [
                    'carrier' => 'Test',
                ],
            ])
            ->patch('/api/v1/shipments/99999');

        $response->assertStatus(404);
    }
}
