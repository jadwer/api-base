<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesOrderIndexTest extends TestCase
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

    public function test_admin_can_list_sales_orders(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_sort_sales_orders_by_order_number(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-2024-002'
        ]);
        SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-2024-001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?sort=order_number');

        $response->assertOk();
        $orderNumbers = collect($response->json('data'))->pluck('attributes.order_number');
        $this->assertEquals(['SO-2024-001', 'SO-2024-002'], $orderNumbers->toArray());
    }

    public function test_admin_can_filter_sales_orders_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'status' => 'confirmed'
        ]);
        SalesOrder::factory()->count(1)->create([
            'customer_id' => $customer->id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders?filter[status]=confirmed');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_admin_can_filter_sales_orders_by_customer(): void
    {
        $admin = $this->getAdminUser();
        
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        
        SalesOrder::factory()->count(2)->create(['customer_id' => $customer1->id]);
        SalesOrder::factory()->count(1)->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders?filter[customer]={$customer1->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_tech_user_can_list_sales_orders_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(2)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_user_can_list_sales_orders(): void
    {
        $customer = $this->getCustomerUser();
        
        $customerModel = Customer::factory()->create();
        SalesOrder::factory()->count(2)->create(['customer_id' => $customerModel->id]);

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
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(25)->create(['customer_id' => $customer->id]);

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

    public function test_can_search_sales_orders_by_order_number(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-SEARCH-001'
        ]);
        SalesOrder::factory()->create([
            'customer_id' => $customer->id,
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
