<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Tests\TestCase;

/**
 * Hallazgos de AtomoPlatform 22-10: orden de compra con alta atomica, total
 * calculado en el servidor y partidas bloqueadas al recibirse o cancelarse.
 */
class PurchaseOrderServerTotalsTest extends TestCase
{
    public function test_with_items_creates_order_atomically_with_server_total(): void
    {
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/purchase-orders/with-items', [
            'contact_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 33.333, 'discount' => 0.5],
            ],
        ]);

        $response->assertCreated();
        $order = PurchaseOrder::findOrFail($response->json('data.id'));
        $this->assertEqualsWithDelta(99.50, (float) $order->total_amount, 0.001);
        $this->assertNotEmpty($order->order_number);
    }

    public function test_items_of_received_orders_are_locked(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => 'received']);
        $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order->id]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->delete("/api/v1/purchase-order-items/{$item->id}")
            ->assertForbidden();
    }
}
