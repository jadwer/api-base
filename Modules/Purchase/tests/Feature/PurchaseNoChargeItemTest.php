<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Support\NoChargeItem;
use Tests\TestCase;

/**
 * Partida de compra a costo 0 solo si se marca sin costo (VAM 2026-10-28):
 * un precio olvidado entraba al inventario a costo 0.
 */
class PurchaseNoChargeItemTest extends TestCase
{
    private function withItems(array $item)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/purchase-orders/with-items', [
            'contact_id' => Contact::factory()->create(['is_supplier' => true])->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [array_merge(['product_id' => Product::factory()->create()->id, 'quantity' => 2, 'discount' => 0], $item)],
        ]);
    }

    public function test_with_items_rejects_zero_cost_without_the_mark(): void
    {
        $before = PurchaseOrder::count();

        $this->withItems(['unit_price' => 0])
            ->assertStatus(422)
            ->assertJsonFragment([NoChargeItem::MESSAGE]);

        $this->assertSame($before, PurchaseOrder::count());
    }

    public function test_with_items_accepts_zero_cost_marked_as_no_charge(): void
    {
        $response = $this->withItems(['unit_price' => 0, 'no_charge' => true])->assertCreated();

        $item = PurchaseOrderItem::where('purchase_order_id', $response->json('data.id'))->firstOrFail();
        $this->assertTrue($item->metadata['no_charge']);
        $this->assertEquals(0, (float) $item->unit_price);
    }

    public function test_jsonapi_item_rejects_zero_cost_without_the_mark(): void
    {
        $order = PurchaseOrder::factory()->create();

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->withData([
            'type' => 'purchase-order-items',
            'attributes' => [
                'purchaseOrderId' => $order->id,
                'productId' => Product::factory()->create()->id,
                'quantity' => 1,
                'unitPrice' => 0,
            ],
        ])->post('/api/v1/purchase-order-items')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.detail', NoChargeItem::MESSAGE);
    }

    public function test_jsonapi_item_accepts_zero_cost_marked_as_no_charge(): void
    {
        $order = PurchaseOrder::factory()->create();

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->withData([
            'type' => 'purchase-order-items',
            'attributes' => [
                'purchaseOrderId' => $order->id,
                'productId' => Product::factory()->create()->id,
                'quantity' => 1,
                'unitPrice' => 0,
                'metadata' => ['no_charge' => true],
            ],
        ])->post('/api/v1/purchase-order-items')
            ->assertCreated();
    }
}
