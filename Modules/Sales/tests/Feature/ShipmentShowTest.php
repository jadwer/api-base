<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Tests for Shipment Show endpoint.
 */
class ShipmentShowTest extends TestCase
{
    public function test_admin_can_view_shipment(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $shipment->id);
        $response->assertJsonPath('data.attributes.shipmentNumber', $shipment->shipment_number);
        $response->assertJsonPath('data.attributes.status', $shipment->status);
    }

    public function test_admin_can_view_shipment_with_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->includePaths('salesOrder')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'salesOrder',
                ],
            ],
            'included',
        ]);
    }

    public function test_admin_can_view_shipment_with_items(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->includePaths('items')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
    }

    public function test_tech_user_can_view_shipment(): void
    {
        $tech = $this->getTechUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
    }

    public function test_customer_can_view_shipment(): void
    {
        $customerUser = $this->getCustomerUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($customerUser, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
    }

    public function test_guest_cannot_view_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(401);
    }

    public function test_viewing_nonexistent_shipment_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments/99999');

        $response->assertStatus(404);
    }

    public function test_shipment_response_contains_expected_attributes(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->shipped()->withTracking()->create([
            'sales_order_id' => $order->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments/{$shipment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'shipmentNumber',
                    'status',
                    'carrier',
                    'trackingNumber',
                    'shipDate',
                    'shippingCost',
                    'createdAt',
                    'updatedAt',
                ],
                'relationships' => [
                    'salesOrder',
                    'items',
                ],
            ],
        ]);
    }
}
