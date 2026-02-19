<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;

class SalesOrderItemShowTest extends TestCase
{



    public function test_admin_can_view_sales_order_item(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 5.0,
            'unit_price' => 100.0,
            'discount' => 10.0,
            'total' => 490.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'sales-order-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 5.0,
                    'unitPrice' => 100.0,
                    'discount' => 10.0,
                    'total' => 490.0
                ]
            ]
        ]);
    }

    public function test_admin_can_view_sales_order_item_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'metadata' => [
                'notes' => 'Special handling required',
                'warehouse_location' => 'A1'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'metadata' => [
                        'notes' => 'Special handling required',
                        'warehouse_location' => 'A1'
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_view_sales_order_item_with_sales_order_id(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'salesOrderId' => $salesOrder->id
                ]
            ]
        ]);
    }

    public function test_admin_can_view_sales_order_item_with_product_id(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $item = SalesOrderItem::factory()->create([
            'product_id' => $product->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'productId' => $product->id
                ]
            ]
        ]);
    }

    public function test_admin_can_view_sales_order_item_with_all_foreign_keys(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $product = Product::factory()->create();
        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'salesOrderId' => $salesOrder->id,
                    'productId' => $product->id
                ]
            ]
        ]);
    }

    public function test_admin_can_view_sales_order_item_with_relationships(): void
    {
        $admin = $this->getAdminUser();
        $customer = Contact::factory()->customer()->create(['name' => 'Test Customer']);
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-REL-001'
        ]);
        $product = Product::factory()->create(['name' => 'Test Product']);
        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 2.0,
            'unit_price' => 50.0,
            'total' => 100.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->includePaths('salesOrder', 'product')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        // Verificar que se incluyen las relaciones
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'salesOrder' => ['data'],
                    'product' => ['data']
                ]
            ],
            'included'
        ]);
        
        // Verificar que los datos están en included
        $included = $response->json('included');
        $this->assertCount(2, $included);
        
        // Buscar el sales order y product en included
        $salesOrderIncluded = collect($included)->firstWhere('type', 'sales-orders');
        $productIncluded = collect($included)->firstWhere('type', 'products');
        
        $this->assertNotNull($salesOrderIncluded);
        $this->assertNotNull($productIncluded);
        $this->assertEquals((string) $salesOrder->id, $salesOrderIncluded['id']);
        $this->assertEquals((string) $product->id, $productIncluded['id']);
    }

    public function test_admin_can_view_sales_order_item_with_nested_relationships(): void
    {
        $admin = $this->getAdminUser();
        
        // Create customer contact for the test
        $customer = \Modules\Contacts\Models\Contact::factory()->create([
            'name' => 'Nested Customer',
            'email' => 'nested@customer.com',
            'classification' => 'minorista',
            'credit_limit' => 10000.00,
            'current_credit' => 0.00,
            'is_customer' => true,
            'is_supplier' => false,
            'status' => 'active',
            'contact_type' => 'company'
        ]);
        
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-NESTED-001'
        ]);
        $product = Product::factory()->create(['name' => 'Nested Product']);
        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 3.0,
            'unit_price' => 75.0,
            'total' => 225.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->includePaths('salesOrder', 'product')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        
        // Verificar que se incluyen las relaciones
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'salesOrder' => ['data'],
                    'product' => ['data']
                ]
            ],
            'included'
        ]);
        
        // Verificar que los datos están en included
        $included = $response->json('included');
        $this->assertCount(2, $included); // salesOrder + product
        
        // Buscar cada tipo en included
        $salesOrderIncluded = collect($included)->firstWhere('type', 'sales-orders');
        $productIncluded = collect($included)->firstWhere('type', 'products');
        
        $this->assertNotNull($salesOrderIncluded);
        $this->assertNotNull($productIncluded);
        $this->assertEquals((string) $salesOrder->id, $salesOrderIncluded['id']);
        $this->assertEquals((string) $product->id, $productIncluded['id']);
    }

    public function test_tech_user_can_view_sales_order_item_with_permission(): void
    {
        $tech = $this->getTechUser();
        $item = SalesOrderItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes'
            ]
        ]);
    }

    public function test_customer_user_can_view_sales_order_item(): void
    {
        $customer = $this->getCustomerUser();
        $item = SalesOrderItem::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes'
            ]
        ]);
    }

    public function test_guest_cannot_view_sales_order_item(): void
    {
        $item = SalesOrderItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('sales-order-items')
            ->get("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_sales_order_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->get('/api/v1/sales-order-items/99999');

        $response->assertNotFound();
    }
}
