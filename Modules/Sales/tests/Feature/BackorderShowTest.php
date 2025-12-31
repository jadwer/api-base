<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Tests for Backorder show endpoint.
 */
class BackorderShowTest extends TestCase
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

    public function test_admin_can_show_backorder(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id);

        $response->assertOk();
        $this->assertEquals((string) $backorder->id, $response->json('data.id'));
    }

    public function test_response_includes_all_attributes(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder([
            'status' => 'pending',
            'priority' => 'high',
            'original_quantity' => 10,
            'backorder_quantity' => 5,
            'fulfilled_quantity' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'pending',
                    'priority' => 'high',
                    'originalQuantity' => 10,
                    'backorderQuantity' => 5,
                    'fulfilledQuantity' => 0,
                ],
            ],
        ]);
    }

    public function test_can_include_sales_order_relationship(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id . '?include=salesOrder');

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
            ->get('/api/v1/backorders/' . $backorder->id . '?include=product');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_can_include_sales_order_item_relationship(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id . '?include=salesOrderItem');

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_tech_can_show_backorder(): void
    {
        $user = $this->getTechUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id);

        $response->assertOk();
    }

    public function test_guest_cannot_show_backorder(): void
    {
        $backorder = $this->createBackorder();

        $response = $this->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_backorder(): void
    {
        $user = $this->getAdminUser();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/999999');

        $response->assertStatus(404);
    }

    public function test_remaining_quantity_is_calculated(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder([
            'backorder_quantity' => 10,
            'fulfilled_quantity' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->get('/api/v1/backorders/' . $backorder->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'remainingQuantity' => 7,
                ],
            ],
        ]);
    }
}
