<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * Hallazgos de AtomoPlatform 22-10 sobre pedidos: alta atomica, totales e IVA
 * calculados en el servidor y partidas bloqueadas por estado del documento.
 */
class SalesOrderServerTotalsTest extends TestCase
{
    private function product(float $price = 100, bool $iva = true): Product
    {
        return Product::factory()->create(['price' => $price, 'iva' => $iva]);
    }

    public function test_with_items_creates_order_atomically_with_server_tax(): void
    {
        $contact = Contact::factory()->customer()->create();
        $a = $this->product(100);
        $b = $this->product(50, false);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/sales-orders/with-items', [
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [
                ['product_id' => $a->id, 'quantity' => 2.5, 'unit_price' => 100, 'discount' => 10],
                ['product_id' => $b->id, 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $response->assertCreated();
        $order = SalesOrder::findOrFail($response->json('data.id'));
        // 250 - 10 = 240 con IVA 16% (38.40) + 50 sin IVA
        $this->assertEqualsWithDelta(290.00, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(38.40, (float) $order->tax_amount, 0.001);
        $this->assertEqualsWithDelta(328.40, (float) $order->total_amount, 0.001);
        $this->assertCount(2, $order->items);
        $this->assertNotEmpty($order->order_number);
    }

    public function test_failed_item_rolls_back_whole_order(): void
    {
        $contact = Contact::factory()->customer()->create();
        $before = SalesOrder::count();

        $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/sales-orders/with-items', [
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [
                ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 100],
                ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 10, 'discount' => 50],
            ],
        ])->assertStatus(422)->assertJsonFragment(['El descuento de la partida no puede ser mayor que su importe.']);

        $this->assertSame($before, SalesOrder::count());
    }

    public function test_item_changes_by_api_recalculate_and_client_totals_are_ignored(): void
    {
        $admin = $this->getAdminUser();
        $order = SalesOrder::factory()->create(['status' => 'pending', 'subtotal' => 0, 'tax_amount' => 0, 'total_amount' => 0, 'discount_total' => 0]);
        $product = $this->product(100);

        $this->actingAs($admin, 'sanctum')->jsonApi()->expects('sales-order-items')->withData([
            'type' => 'sales-order-items',
            'attributes' => ['salesOrderId' => $order->id, 'productId' => $product->id, 'quantity' => 1, 'unitPrice' => 100, 'discount' => 0, 'total' => 999],
        ])->post('/api/v1/sales-order-items')->assertCreated();

        $this->assertEqualsWithDelta(116.00, (float) $order->fresh()->total_amount, 0.001);

        // El navegador ya no puede fijar totales
        $this->actingAs($admin, 'sanctum')->jsonApi()->expects('sales-orders')->withData([
            'type' => 'sales-orders', 'id' => (string) $order->id,
            'attributes' => ['totalAmount' => 1, 'taxAmount' => 0],
        ])->patch("/api/v1/sales-orders/{$order->id}");
        $this->assertEqualsWithDelta(116.00, (float) $order->fresh()->total_amount, 0.001);

        $item = SalesOrderItem::where('sales_order_id', $order->id)->firstOrFail();
        $this->actingAs($admin, 'sanctum')->jsonApi()->delete("/api/v1/sales-order-items/{$item->id}")->assertNoContent();
        $this->assertEqualsWithDelta(0.0, (float) $order->fresh()->total_amount, 0.001);
    }

    public function test_items_of_closed_orders_are_locked(): void
    {
        $admin = $this->getAdminUser();
        $order = SalesOrder::factory()->create(['status' => 'cancelled']);
        $item = SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'unit_price' => 10, 'quantity' => 1]);

        $this->actingAs($admin, 'sanctum')->jsonApi()->delete("/api/v1/sales-order-items/{$item->id}")
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')->jsonApi()->expects('sales-order-items')->withData([
            'type' => 'sales-order-items',
            'attributes' => ['salesOrderId' => $order->id, 'productId' => $this->product()->id, 'quantity' => 1, 'unitPrice' => 100, 'discount' => 0],
        ])->post('/api/v1/sales-order-items')->assertForbidden();

        $this->assertDatabaseHas('sales_order_items', ['id' => $item->id]);
    }

    public function test_items_of_converted_quotes_are_locked(): void
    {
        $quote = Quote::factory()->create(['status' => 'converted']);
        $item = QuoteItem::factory()->create(['quote_id' => $quote->id]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->delete("/api/v1/quote-items/{$item->id}")
            ->assertForbidden();
    }
}
