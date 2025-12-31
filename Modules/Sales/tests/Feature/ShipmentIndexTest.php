<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Tests for Shipment Index endpoint.
 */
class ShipmentIndexTest extends TestCase
{
    public function test_admin_can_list_shipments(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->count(3)->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_filter_shipments_by_status(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->count(2)->pending()->create(['sales_order_id' => $order->id]);
        Shipment::factory()->shipped()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments?filter[status]=pending');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_shipments_by_order(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order1 = SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'confirmed']);
        $order2 = SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'confirmed']);

        Shipment::factory()->count(2)->create(['sales_order_id' => $order1->id]);
        Shipment::factory()->create(['sales_order_id' => $order2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get("/api/v1/shipments?filter[salesOrder]={$order1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_sort_shipments_by_ship_date(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->shipped()->create([
            'sales_order_id' => $order->id,
            'ship_date' => '2025-01-01',
        ]);
        Shipment::factory()->shipped()->create([
            'sales_order_id' => $order->id,
            'ship_date' => '2025-12-31',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments?sort=shipDate');

        $response->assertOk();
    }

    public function test_tech_user_can_list_shipments(): void
    {
        $tech = $this->getTechUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->count(2)->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments');

        $response->assertOk();
    }

    public function test_customer_can_view_shipments(): void
    {
        $customer = $this->getCustomerUser();

        $contact = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->count(2)->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments');

        $response->assertOk();
    }

    public function test_guest_cannot_list_shipments(): void
    {
        $response = $this->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments');

        $response->assertStatus(401);
    }

    public function test_can_include_sales_order_in_shipments(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->includePaths('salesOrder')
            ->get('/api/v1/shipments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                    'relationships' => [
                        'salesOrder',
                    ],
                ],
            ],
            'included',
        ]);
    }

    public function test_can_paginate_shipments(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        Shipment::factory()->count(15)->create(['sales_order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipments')
            ->get('/api/v1/shipments?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => ['page'],
        ]);
    }
}
