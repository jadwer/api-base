<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;

class SalesOrderItemDestroyTest extends TestCase
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

    public function test_admin_can_delete_sales_order_item(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
    }

    public function test_admin_can_delete_sales_order_item_with_metadata(): void
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
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
    }

    public function test_deleting_sales_order_item_does_not_affect_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $item = SalesOrderItem::factory()->create(['sales_order_id' => $salesOrder->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
        $this->assertDatabaseHas('sales_orders', ['id' => $salesOrder->id]);
    }

    public function test_deleting_sales_order_item_does_not_affect_product(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $item = SalesOrderItem::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_delete_multiple_items_from_same_sales_order(): void
    {
        $admin = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $item1 = SalesOrderItem::factory()->create(['sales_order_id' => $salesOrder->id]);
        $item2 = SalesOrderItem::factory()->create(['sales_order_id' => $salesOrder->id]);
        $item3 = SalesOrderItem::factory()->create(['sales_order_id' => $salesOrder->id]);

        // Delete first item
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item1->id}");

        $response1->assertNoContent();

        // Delete second item
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item2->id}");

        $response2->assertNoContent();

        // Verify deletions and remaining item
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item2->id]);
        $this->assertDatabaseHas('sales_order_items', ['id' => $item3->id]);
        $this->assertDatabaseHas('sales_orders', ['id' => $salesOrder->id]);
    }

    public function test_customer_user_cannot_delete_sales_order_item(): void
    {
        $customer = $this->getCustomerUser();
        $item = SalesOrderItem::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('sales_order_items', ['id' => $item->id]);
    }

    public function test_guest_cannot_delete_sales_order_item(): void
    {
        $item = SalesOrderItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('sales_order_items', ['id' => $item->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_sales_order_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete('/api/v1/sales-order-items/99999');

        $response->assertNotFound();
    }

    public function test_tech_user_can_delete_sales_order_item(): void
    {
        $tech = $this->getTechUser();
        $item = SalesOrderItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
    }

    public function test_admin_can_delete_sales_order_item_with_high_values(): void
    {
        $admin = $this->getAdminUser();
        $item = SalesOrderItem::factory()->create([
            'quantity' => 1000.0,
            'unit_price' => 5000.0,
            'total' => 5000000.0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-order-items')
            ->delete("/api/v1/sales-order-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('sales_order_items', ['id' => $item->id]);
    }
}
