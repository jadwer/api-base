<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;

class SalesOrderItemStoreTest extends TestCase
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

    public function test_admin_can_create_sales_order_item(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 5.0,
                'unitPrice' => 100.0,
                'discount' => 10.0,
                'total' => 490.0,
                'metadata' => [
                    'notes' => 'Special handling required'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertCreated();
        
        $id = $response->json('data.id');
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 5.0,
            'unit_price' => 100.0,
            'discount' => 10.0,
            'total' => 490.0
        ]);
    }

    public function test_admin_can_create_sales_order_item_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 2.0,
                'unitPrice' => 50.0,
                'total' => 100.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertCreated();
        
        $id = $response->json('data.id');
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 2.0,
            'unit_price' => 50.0,
            'discount' => 0.0, // Default value
            'total' => 100.0
        ]);
    }

    public function test_customer_user_cannot_create_sales_order_item(): void
    {
        $customer = $this->getCustomerUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 1.0,
                'unitPrice' => 100.0,
                'total' => 100.0
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertForbidden();
    }

    public function test_guest_cannot_create_sales_order_item(): void
    {
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 1.0,
                'unitPrice' => 100.0,
                'total' => 100.0
            ]
        ];

        $response = $this->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertStatus(401);
    }

    public function test_cannot_create_sales_order_item_without_required_fields(): void
    {
        $admin = $this->getAdminUser();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => (object) []
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/salesOrderId', $errorPointers);
        $this->assertContains('/data/attributes/productId', $errorPointers);
        $this->assertContains('/data/attributes/quantity', $errorPointers);
        $this->assertContains('/data/attributes/unitPrice', $errorPointers);
        $this->assertContains('/data/attributes/total', $errorPointers);
    }

    public function test_cannot_create_sales_order_item_with_negative_quantity(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => -1.0,
                'unitPrice' => 100.0,
                'total' => 100.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/quantity', $errorPointers);
    }

    public function test_cannot_create_sales_order_item_with_negative_unit_price(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 1.0,
                'unitPrice' => -50.0,
                'total' => 100.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/unitPrice', $errorPointers);
    }

    public function test_cannot_create_sales_order_item_with_negative_discount(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 1.0,
                'unitPrice' => 100.0,
                'discount' => -10.0,
                'total' => 110.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/discount', $errorPointers);
    }

    public function test_tech_user_can_create_sales_order_item(): void
    {
        $tech = $this->getTechUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'productId' => $product->id,
                'quantity' => 1.0,
                'unitPrice' => 100.0,
                'total' => 100.0
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->post('/api/v1/sales-order-items');

        $response->assertCreated();
        
        $id = $response->json('data.id');
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 1.0,
            'unit_price' => 100.0,
            'total' => 100.0
        ]);
    }
}
