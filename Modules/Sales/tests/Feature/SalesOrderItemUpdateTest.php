<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;

class SalesOrderItemUpdateTest extends TestCase
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

    public function test_admin_can_update_sales_order_item_quantity(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 5.0,
            'unit_price' => 100.0,
            'total' => 500.0
        ]);
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 10.0,
                'total' => 1000.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'quantity' => 10.0,
            'total' => 1000.0
        ]);
    }

    public function test_admin_can_update_sales_order_item_unit_price(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 5.0,
            'unit_price' => 100.0,
            'total' => 500.0
        ]);
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'unitPrice' => 150.0,
                'total' => 750.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'unit_price' => 150.0,
            'total' => 750.0
        ]);
    }

    public function test_admin_can_update_sales_order_item_discount(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 5.0,
            'unit_price' => 100.0,
            'discount' => 0.0,
            'total' => 500.0
        ]);
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'discount' => 50.0,
                'total' => 450.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'discount' => 50.0,
            'total' => 450.0
        ]);
    }

    public function test_admin_can_update_sales_order_item_metadata(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'metadata' => ['notes' => 'Original note']
        ]);
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'metadata' => [
                    'notes' => 'Updated note',
                    'warehouse_location' => 'B2'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $item->refresh();
        $this->assertEquals([
            'notes' => 'Updated note',
            'warehouse_location' => 'B2'
        ], $item->metadata);
    }

    public function test_admin_can_update_sales_order_item_product_id(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create();
        $newProduct = Product::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'productId' => $newProduct->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'product_id' => $newProduct->id
        ]);
    }

    public function test_customer_user_cannot_update_sales_order_item(): void
    {
        $customer = $this->getCustomerUser();
        $item = SalesOrderItem::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 10.0
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_update_sales_order_item(): void
    {
        $item = SalesOrderItem::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 10.0
            ]
        ];

        $response = $this->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_sales_order_item_with_negative_quantity(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => -5.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/quantity', $errorPointers);
    }

    public function test_cannot_update_sales_order_item_with_negative_unit_price(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'unitPrice' => -100.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/unitPrice', $errorPointers);
    }

    public function test_cannot_update_sales_order_item_with_negative_discount(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create();
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'discount' => -10.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $errorPointers = collect($errors)->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/discount', $errorPointers);
    }

    public function test_tech_user_can_update_sales_order_item(): void
    {
        $tech = $this->getTechUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 5.0,
            'total' => 500.0
        ]);
        
        $data = [
            'type' => 'sales-order-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 10.0,
                'total' => 1000.0
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->withData($data)
            ->patch("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'quantity' => 10.0,
            'total' => 1000.0
        ]);
    }
}
