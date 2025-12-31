<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Tests for Backorder index endpoint.
 */
class BackorderIndexTest extends TestCase
{
    protected function createBackorder(array $attributes = []): Backorder
    {
        $customer = Contact::factory()->customer()->create();
        $product = Product::factory()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $orderItem = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        return Backorder::factory()->create(array_merge([
            'sales_order_id' => $order->id,
            'sales_order_item_id' => $orderItem->id,
            'product_id' => $product->id,
        ], $attributes));
    }

    public function test_admin_can_list_backorders(): void
    {
        $user = $this->getAdminUser();
        $this->createBackorder();
        $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_filter_backorders_by_status(): void
    {
        $user = $this->getAdminUser();
        $this->createBackorder(['status' => 'pending']);
        $this->createBackorder(['status' => 'fulfilled']);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?filter[status]=pending');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_filter_backorders_by_priority(): void
    {
        $user = $this->getAdminUser();
        $this->createBackorder(['priority' => 'urgent']);
        $this->createBackorder(['priority' => 'low']);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?filter[priority]=urgent');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_sort_backorders_by_created_at(): void
    {
        $user = $this->getAdminUser();
        $this->createBackorder();
        $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?sort=-createdAt');

        $response->assertOk();
    }

    public function test_can_include_sales_order_relationship(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?include=salesOrder');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_can_include_product_relationship(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?include=product');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_tech_can_list_backorders(): void
    {
        $user = $this->getTechUser();
        $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders');

        $response->assertOk();
    }

    public function test_guest_cannot_list_backorders(): void
    {
        $this->createBackorder();

        $response = $this->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders');

        $response->assertStatus(401);
    }

    public function test_response_is_paginated(): void
    {
        $user = $this->getAdminUser();
        for ($i = 0; $i < 5; $i++) {
            $this->createBackorder();
        }

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders?page[number]=1&page[size]=2');

        $response->assertOk();
        $this->assertArrayHasKey('meta', $response->json());
    }
}
