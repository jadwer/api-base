<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Tests for Shipment Destroy endpoint.
 */
class ShipmentDestroyTest extends TestCase
{
    public function test_admin_can_delete_pending_shipment(): void
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
            ->delete("/api/v1/shipments/{$shipment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('shipments', ['id' => $shipment->id]);
    }

    public function test_tech_user_can_delete_shipment(): void
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
            ->delete("/api/v1/shipments/{$shipment->id}");

        $response->assertNoContent();
    }

    public function test_customer_cannot_delete_shipment(): void
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
            ->delete("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_delete_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create(['sales_order_id' => $order->id]);

        $response = $this->jsonApi()
            ->expects('shipments')
            ->delete("/api/v1/shipments/{$shipment->id}");

        $response->assertStatus(401);
    }

    public function test_deleting_nonexistent_shipment_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->delete('/api/v1/shipments/99999');

        $response->assertStatus(404);
    }
}
