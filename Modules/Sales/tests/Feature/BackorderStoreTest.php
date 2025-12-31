<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Tests for Backorder store endpoint.
 */
class BackorderStoreTest extends TestCase
{
    protected function createOrderWithItem(): array
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

        return [$order, $orderItem, $product];
    }

    public function test_admin_can_create_backorder(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
                'priority' => 'normal',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertCreated();

        $this->assertDatabaseHas('backorders', [
            'sales_order_id' => $order->id,
            'sales_order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'original_quantity' => 10,
            'backorder_quantity' => 5,
            'priority' => 'normal',
            'status' => 'pending',
        ]);
    }

    public function test_backorder_number_is_generated(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertCreated();

        $backorder = Backorder::latest()->first();
        $this->assertNotNull($backorder->backorder_number);
        $this->assertStringStartsWith('BO-', $backorder->backorder_number);
    }

    public function test_can_create_backorder_with_priority(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
                'priority' => 'urgent',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertCreated();

        $this->assertDatabaseHas('backorders', [
            'sales_order_id' => $order->id,
            'priority' => 'urgent',
        ]);
    }

    public function test_can_create_backorder_with_expected_date(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();
        $expectedDate = now()->addDays(7)->format('Y-m-d');

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
                'expectedDate' => $expectedDate,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertCreated();

        $this->assertDatabaseHas('backorders', [
            'sales_order_id' => $order->id,
            'expected_date' => $expectedDate,
        ]);
    }

    public function test_tech_can_create_backorder(): void
    {
        $user = $this->getTechUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertCreated();
    }

    public function test_customer_cannot_create_backorder(): void
    {
        $user = $this->getCustomerUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertStatus(401);
    }

    public function test_validation_requires_sales_order_id(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertStatus(422);
    }

    public function test_validation_requires_product_id(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'originalQuantity' => 10,
                'backorderQuantity' => 5,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertStatus(422);
    }

    public function test_validation_requires_backorder_quantity(): void
    {
        $user = $this->getAdminUser();
        [$order, $orderItem, $product] = $this->createOrderWithItem();

        $data = [
            'type' => 'backorders',
            'attributes' => [
                'salesOrderId' => $order->id,
                'salesOrderItemId' => $orderItem->id,
                'productId' => $product->id,
                'originalQuantity' => 10,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->post('/api/v1/backorders');

        $response->assertStatus(422);
    }
}
