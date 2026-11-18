<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Tests\TestCase;

/**
 * B4 (revision de AtomoPlatform 2026-11-16): la cantidad de la partida de compra era integer y
 * una compra a granel (2.5 L) se guardaba como 3 con el subtotal calculado sobre 2.5.
 */
class PurchaseOrderBulkQuantityTest extends TestCase
{
    public function test_with_items_keeps_fractional_quantity_and_consistent_totals(): void
    {
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/purchase-orders/with-items', [
            'contact_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 2.5, 'unit_price' => 100, 'discount' => 0]],
        ]);

        $response->assertCreated();
        $order = PurchaseOrder::findOrFail($response->json('data.id'));
        $item = $order->purchaseOrderItems()->firstOrFail()->fresh();
        $this->assertSame(2.5, (float) $item->quantity);
        $this->assertEqualsWithDelta(250.0, (float) $item->subtotal, 0.001);
        $this->assertEqualsWithDelta(250.0, (float) $order->total_amount, 0.01);
    }

    public function test_item_update_accepts_four_decimals(): void
    {
        $item = PurchaseOrderItem::factory()->create(['quantity' => 1, 'unit_price' => 10, 'discount' => 0]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'purchase-order-items', 'id' => (string) $item->id, 'attributes' => ['quantity' => 0.125]])
            ->patch("/api/v1/purchase-order-items/{$item->id}")
            ->assertOk();

        $this->assertSame(0.125, (float) $item->fresh()->quantity);
    }
}
