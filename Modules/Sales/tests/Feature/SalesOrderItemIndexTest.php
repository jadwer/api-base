<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;

class SalesOrderItemIndexTest extends TestCase
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

    public function test_admin_can_list_sales_order_items(): void
    {
        $admin = $this->getAdminUser();
        
        SalesOrderItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_sort_sales_order_items_by_quantity(): void
    {
        $admin = $this->getAdminUser();
        
        SalesOrderItem::factory()->create(['quantity' => 10.0]);
        SalesOrderItem::factory()->create(['quantity' => 5.0]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items?sort=quantity');

        $response->assertOk();
        $quantities = collect($response->json('data'))->pluck('attributes.quantity');
        $this->assertEquals([5.0, 10.0], $quantities->toArray());
    }

    public function test_admin_can_sort_sales_order_items_by_total_desc(): void
    {
        $admin = $this->getAdminUser();
        
        SalesOrderItem::factory()->create(['total' => 100.0]);
        SalesOrderItem::factory()->create(['total' => 200.0]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items?sort=-total');

        $response->assertOk();
        $totals = collect($response->json('data'))->pluck('attributes.total');
        $this->assertEquals([200.0, 100.0], $totals->toArray());
    }

    public function test_admin_can_filter_sales_order_items_by_sales_order(): void
    {
        $admin = $this->getAdminUser();
        
        $salesOrder1 = SalesOrder::factory()->create();
        $salesOrder2 = SalesOrder::factory()->create();
        
        SalesOrderItem::factory()->count(2)->create(['sales_order_id' => $salesOrder1->id]);
        SalesOrderItem::factory()->count(1)->create(['sales_order_id' => $salesOrder2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items?filter[salesOrderId]={$salesOrder1->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_admin_can_filter_sales_order_items_by_product(): void
    {
        $admin = $this->getAdminUser();
        
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        SalesOrderItem::factory()->count(2)->create(['product_id' => $product1->id]);
        SalesOrderItem::factory()->count(1)->create(['product_id' => $product2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items?filter[productId]={$product1->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_tech_user_can_list_sales_order_items_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        SalesOrderItem::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_user_can_list_sales_order_items(): void
    {
        $customer = $this->getCustomerUser();
        SalesOrderItem::factory()->count(3)->create();
        
        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes'
                ]
            ]
        ]);
    }

    public function test_guest_cannot_list_sales_order_items(): void
    {
        $response = $this->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items');

        $response->assertStatus(401);
    }

    public function test_can_paginate_sales_order_items(): void
    {
        $admin = $this->getAdminUser();
        
        SalesOrderItem::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'links' => ['first', 'last', 'next'],
            'meta' => ['page']
        ]);
    }
}
