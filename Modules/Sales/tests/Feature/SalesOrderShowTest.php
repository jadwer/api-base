<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesOrderShowTest extends TestCase
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

    public function test_admin_can_view_sales_order(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-TEST-001',
            'status' => 'confirmed',
            'total_amount' => 1500.00,
            'discount_total' => 150.00
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        // Verificar estructura básica
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'order_number',
                    'status',
                    'total_amount',
                    'discount_total',
                ]
            ]
        ]);

        // Verificar datos específicos
        $this->assertEquals('SO-TEST-001', $response->json('data.attributes.order_number'));
        $this->assertEquals('confirmed', $response->json('data.attributes.status'));
        $this->assertEquals(1500.00, $response->json('data.attributes.total_amount'));
        $this->assertEquals(150.00, $response->json('data.attributes.discount_total'));
    }

    public function test_admin_can_view_sales_order_with_relationships(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create(['name' => 'Test Customer Relationship']);
        $salesOrder = SalesOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}?include=customer");

        $response->assertOk();
        
        // Verificar que incluye la relación customer
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'customer' => [
                        'data'
                    ]
                ]
            ],
            'included'
        ]);
    }

    public function test_admin_can_view_draft_sales_order(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->draft()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-DRAFT-001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        $this->assertEquals('SO-DRAFT-001', $response->json('data.attributes.order_number'));
    }

    public function test_tech_user_can_view_sales_order_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
    }

    public function test_customer_user_can_view_sales_order(): void
    {
        $customer = $this->getCustomerUser();
        
        $customerModel = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['customer_id' => $customerModel->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes'
            ]
        ]);
    }

    public function test_guest_cannot_view_sales_order(): void
    {
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_view_nonexistent_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.created_at'));
        $this->assertNotNull($response->json('data.attributes.updated_at'));
    }

    public function test_metadata_is_properly_formatted(): void
    {
        $admin = $this->getAdminUser();
        
        $metadata = [
            'priority' => 'high',
            'source' => 'web',
            'payment_terms' => '30_days'
        ];
        
        $customer = Customer::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'metadata' => $metadata
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        $responseMetadata = $response->json('data.attributes.metadata');
        $this->assertEquals($metadata, $responseMetadata);
        $this->assertEquals('high', $responseMetadata['priority']);
        $this->assertEquals('web', $responseMetadata['source']);
    }

    public function test_admin_can_view_sales_order_with_items(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create(['name' => 'Test Customer']);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-ITEMS-001'
        ]);
        
        // Crear 2 items para el SalesOrder
        $item1 = \Modules\Sales\Models\SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'quantity' => 1.0,
            'unit_price' => 100.0,
            'total' => 100.0
        ]);
        
        $item2 = \Modules\Sales\Models\SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'quantity' => 2.0,
            'unit_price' => 50.0,
            'total' => 100.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->includePaths('items')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        // Verificar que se incluye la relación items
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'items' => ['data']
                ]
            ],
            'included'
        ]);
        
        // Verificar que hay 2 items en included
        $included = $response->json('included');
        $this->assertCount(2, $included);
        
        // Verificar que todos los elementos en included son sales-order-items
        $itemTypes = collect($included)->pluck('type')->unique();
        $this->assertEquals(['sales-order-items'], $itemTypes->values()->toArray());
        
        // Verificar que al menos encontramos algunos items (sin importar IDs específicos)
        $this->assertGreaterThan(0, count($included));
    }

    public function test_admin_can_view_sales_order_with_nested_items_and_products(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create(['name' => 'Nested Customer']);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-NESTED-PROD-001'
        ]);
        
        // Crear 2 productos diferentes
        $product1 = \Modules\Product\Models\Product::factory()->create(['name' => 'Product A']);
        $product2 = \Modules\Product\Models\Product::factory()->create(['name' => 'Product B']);
        
        // Crear 2 items con productos diferentes
        $item1 = \Modules\Sales\Models\SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product1->id,
            'quantity' => 1.0,
            'unit_price' => 100.0,
            'total' => 100.0
        ]);
        
        $item2 = \Modules\Sales\Models\SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product2->id,
            'quantity' => 2.0,
            'unit_price' => 50.0,
            'total' => 100.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->includePaths('items.product', 'customer')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        // Verificar que se incluyen las relaciones
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'items' => ['data'],
                    'customer' => ['data']
                ]
            ],
            'included'
        ]);
        
        // Verificar que los datos están en included
        $included = $response->json('included');
        $this->assertCount(5, $included); // 2 items + 2 products + 1 customer
        
        // Buscar cada tipo en included
        $itemsIncluded = collect($included)->where('type', 'sales-order-items');
        $productsIncluded = collect($included)->where('type', 'products');
        $customerIncluded = collect($included)->firstWhere('type', 'customers');
        
        $this->assertEquals(2, $itemsIncluded->count());
        $this->assertEquals(2, $productsIncluded->count());
        $this->assertNotNull($customerIncluded);
        
        // Verificar que cada item tiene relación con su producto
        foreach ($itemsIncluded as $item) {
            $this->assertArrayHasKey('relationships', $item);
            $this->assertArrayHasKey('product', $item['relationships']);
            $this->assertArrayHasKey('data', $item['relationships']['product']);
        }
    }

    public function test_admin_can_view_sales_order_with_hybrid_approach(): void
    {
        $admin = $this->getAdminUser();
        $customer = Customer::factory()->create(['name' => 'Hybrid Customer']);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'SO-HYBRID-001'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->includePaths('customer')
            ->get("/api/v1/sales-orders/{$salesOrder->id}");

        $response->assertOk();
        
        // Verificar que tenemos BOTH el campo directo Y la relación
        $data = $response->json('data');
        
        // Campo directo customerId (BOTH snake_case AND camelCase)
        $this->assertArrayHasKey('customer_id', $data['attributes']);
        $this->assertArrayHasKey('customerId', $data['attributes']);
        $this->assertEquals($customer->id, $data['attributes']['customer_id']);
        $this->assertEquals($customer->id, $data['attributes']['customerId']);
        
        // Relación customer en relationships
        $this->assertArrayHasKey('customer', $data['relationships']);
        $this->assertEquals((string) $customer->id, $data['relationships']['customer']['data']['id']);
        $this->assertEquals('customers', $data['relationships']['customer']['data']['type']);
        
        // Customer en included
        $included = $response->json('included');
        $customerIncluded = collect($included)->firstWhere('type', 'customers');
        $this->assertNotNull($customerIncluded);
        $this->assertEquals((string) $customer->id, $customerIncluded['id']);
        
        // Verificar que campos están en snake_case (compatibilidad existente)
        $this->assertArrayHasKey('order_number', $data['attributes']);
        $this->assertArrayHasKey('total_amount', $data['attributes']);
        $this->assertArrayHasKey('created_at', $data['attributes']);
        $this->assertArrayHasKey('updated_at', $data['attributes']);
    }
}
