<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Tests for Backorder destroy endpoint.
 */
class BackorderDestroyTest extends TestCase
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

    public function test_admin_can_delete_backorder(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();
        $backorderId = $backorder->id;

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('backorders', [
            'id' => $backorderId,
        ]);
    }

    public function test_tech_can_delete_backorder(): void
    {
        $user = $this->getTechUser();
        $backorder = $this->createBackorder();
        $backorderId = $backorder->id;

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('backorders', [
            'id' => $backorderId,
        ]);
    }

    public function test_customer_cannot_delete_backorder(): void
    {
        $user = $this->getCustomerUser();
        $backorder = $this->createBackorder();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
        ]);
    }

    public function test_guest_cannot_delete_backorder(): void
    {
        $backorder = $this->createBackorder();

        $response = $this->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
        ]);
    }

    public function test_cannot_delete_nonexistent_backorder(): void
    {
        $user = $this->getAdminUser();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/999999');

        $response->assertStatus(404);
    }

    public function test_can_delete_pending_backorder(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder(['status' => 'pending']);
        $backorderId = $backorder->id;

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('backorders', [
            'id' => $backorderId,
        ]);
    }

    public function test_can_delete_cancelled_backorder(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder(['status' => 'cancelled']);
        $backorderId = $backorder->id;

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->delete('/api/v1/backorders/' . $backorder->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('backorders', [
            'id' => $backorderId,
        ]);
    }
}
