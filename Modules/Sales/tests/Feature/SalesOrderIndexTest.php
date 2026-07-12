<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;

class SalesOrderIndexTest extends TestCase
{



    public function test_admin_can_list_sales_orders(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->count(3)->create(['contact_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_sales_orders_by_order_number(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-2024-ZZZ' // Will sort last
        ]);
        SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-2024-AAA' // Will sort first
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?sort=orderNumber&page[size]=100');

        $response->assertOk();

        // Verify sorting: first item should have orderNumber starting with 'SO-2024-AAA'
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data), 'Should have at least one sales order');

        // Find our test orders in the response
        $testOrders = collect($data)->filter(function($item) {
            $num = $item['attributes']['orderNumber'] ?? null;
            return $num === 'SO-2024-AAA' || $num === 'SO-2024-ZZZ';
        })->values();

        $this->assertCount(2, $testOrders, 'Should find both test orders');
        $this->assertEquals('SO-2024-AAA', $testOrders[0]['attributes']['orderNumber'], 'First should be AAA (sorted ascending)');
        $this->assertEquals('SO-2024-ZZZ', $testOrders[1]['attributes']['orderNumber'], 'Second should be ZZZ');
    }

    public function test_admin_can_filter_sales_orders_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->count(2)->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed'
        ]);
        SalesOrder::factory()->count(1)->create([
            'contact_id' => $customer->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?filter[status]=confirmed');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_sales_orders_by_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer1 = Contact::factory()->customer()->create();
        $customer2 = Contact::factory()->customer()->create();
        
        SalesOrder::factory()->count(2)->create(['contact_id' => $customer1->id]);
        SalesOrder::factory()->count(1)->create(['contact_id' => $customer2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders?filter[contact]={$customer1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_sales_orders_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->count(2)->create(['contact_id' => $customer->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_can_list_sales_orders(): void
    {
        $customer = $this->getCustomerUser();
        
        $customerModel = Contact::factory()->customer()->create();
        SalesOrder::factory()->count(2)->create(['contact_id' => $customerModel->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        // For now, customer can access (business logic can be refined later)
        $response->assertOk();
    }

    public function test_guest_cannot_list_sales_orders(): void
    {
        $response = $this->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertStatus(401);
    }

    public function test_can_paginate_sales_orders(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->count(25)->create(['contact_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        
        // Verificar que tiene estructura de paginación básica
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        
        // Verificar que el meta tiene información de página
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    /**
     * Nota cliente #11: pedidos "por surtir".
     * El filtro pending_fulfillment debe devolver solo ordenes abiertas
     * (no delivered, completed, cancelled, returned ni refunded).
     */
    public function test_pending_fulfillment_filter_returns_only_open_orders(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();

        // Abiertas (por surtir)
        $open = collect([
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'draft']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'pending']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'confirmed']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'processing']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'shipped']),
        ]);

        // Cerradas (no deben aparecer)
        $closed = collect([
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'delivered']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'completed']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'cancelled']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'returned']),
            SalesOrder::factory()->create(['contact_id' => $customer->id, 'status' => 'refunded']),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?filter[pending_fulfillment]=1&page[size]=100');

        $response->assertOk();

        $returnedIds = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($open as $order) {
            $this->assertTrue($returnedIds->contains($order->id), "Open order {$order->status} should be listed");
        }

        foreach ($closed as $order) {
            $this->assertFalse($returnedIds->contains($order->id), "Closed order {$order->status} should NOT be listed");
        }
    }

    public function test_can_search_sales_orders_by_order_number(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Contact::factory()->customer()->create();
        SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-SEARCH-001'
        ]);
        SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-OTHER-002'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?filter[order_number]=SO-SEARCH-001');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
