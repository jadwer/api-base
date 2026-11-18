<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * B2 (revision de AtomoPlatform 2026-11-16): al convertir, el descuento de la cotizacion se
 * copiaba al encabezado del pedido ademas de venir en cada partida; al editar una partida
 * SalesOrder::recalculateTotals() lo restaba dos veces.
 */
class QuoteConvertDiscountTest extends TestCase
{
    public function test_item_discount_is_not_applied_twice_after_editing_the_order(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'accepted', 'accepted_at' => now()]);
        $product = Product::factory()->create();
        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'quoted_price' => 100,
            'discount_percentage' => 10,
        ]);
        $quote->refresh();
        $this->assertEqualsWithDelta(20, (float) $quote->discount_amount, 0.001);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/convert");
        $response->assertStatus(201);
        $order = SalesOrder::findOrFail($response->json('data.salesOrder.id'));
        $this->assertEqualsWithDelta(0, (float) $order->discount_total, 0.001);

        $item = $order->items()->firstOrFail();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'sales-order-items', 'id' => (string) $item->id, 'attributes' => ['quantity' => 3]])
            ->patch("/api/v1/sales-order-items/{$item->id}")
            ->assertOk();

        $order->refresh();
        $this->assertEqualsWithDelta(280, (float) $order->subtotal, 0.001, 'partida neta: 3 x 100 - 20');
        $this->assertEqualsWithDelta(0, (float) $order->discount_total, 0.001);
        $this->assertEqualsWithDelta((float) $order->subtotal + (float) $order->tax_amount, (float) $order->total_amount, 0.01);
    }
}
