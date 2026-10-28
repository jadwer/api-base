<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Huecos del ciclo de cotizacion detectados por AtomoPlatform (PLAN_OLA2,
 * 2026-10-22): vencimiento que nunca corria, duplicar con vinculos y pedido
 * sin historial.
 */
class QuoteLifecycleRulesTest extends TestCase
{
    private function quote(array $attributes = []): Quote
    {
        $quote = Quote::factory()->create(array_merge([
            'contact_id' => Contact::factory()->customer()->create()->id,
        ], $attributes));

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'quoted_price' => 100,
            'total' => 200,
        ]);

        return $quote;
    }

    public function test_expire_command_only_touches_sent_quotes_past_their_validity(): void
    {
        $old = $this->quote(['status' => 'sent', 'valid_until' => now()->subDays(2)]);
        $lastDay = $this->quote(['status' => 'sent', 'valid_until' => now()]);
        $draft = $this->quote(['status' => 'draft', 'valid_until' => now()->subDays(2)]);
        $accepted = $this->quote(['status' => 'accepted', 'valid_until' => now()->subDays(2)]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame('sent', $lastDay->fresh()->status, 'el ultimo dia de vigencia sigue vigente');
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('accepted', $accepted->fresh()->status);
    }

    public function test_last_day_of_validity_is_not_expired(): void
    {
        $this->assertFalse($this->quote(['valid_until' => now()])->isExpired);
        $this->assertTrue($this->quote(['valid_until' => now()->subDay()])->isExpired);
    }

    public function test_expired_quote_cannot_be_accepted(): void
    {
        $quote = $this->quote(['status' => 'sent', 'valid_until' => now()->subDays(3)]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept")
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'La cotización venció el ' . now()->subDays(3)->format('d/m/Y') . '. Duplícala para renovar la vigencia.']);

        $this->assertSame('sent', $quote->fresh()->status);
    }

    public function test_duplicate_does_not_carry_order_links(): void
    {
        $quote = $this->quote(['status' => 'converted', 'converted_at' => now()]);
        $order = SalesOrder::factory()->create(['contact_id' => $quote->contact_id]);
        $quote->forceFill(['sales_order_id' => $order->id, 'purchase_order_id' => PurchaseOrder::factory()->create()->id])->saveQuietly();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate")
            ->assertStatus(201);

        $copy = Quote::findOrFail($response->json('data.id'));
        $this->assertNull($copy->sales_order_id);
        $this->assertNull($copy->purchase_order_id);
        $this->assertNull($copy->shopping_cart_id);
        $this->assertSame('draft', $copy->status);
        $this->assertTrue($copy->valid_until->isFuture());
    }

    public function test_order_from_quote_starts_its_status_history(): void
    {
        $quote = $this->quote(['status' => 'accepted', 'accepted_at' => now()]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert")
            ->assertStatus(201);

        $order = SalesOrder::findOrFail($response->json('data.salesOrder.id'));
        $history = $order->metadata['status_history'] ?? [];
        $this->assertCount(1, $history);
        $this->assertSame($order->status, $history[0]['to']);
        $this->assertStringContainsString($quote->quote_number, $history[0]['notes']);
    }

    public function test_converted_quote_cannot_be_converted_again(): void
    {
        $quote = $this->quote(['status' => 'accepted', 'accepted_at' => now()]);
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/convert")->assertStatus(201);
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/convert")->assertStatus(400);

        $this->assertSame(1, SalesOrder::where('quote_id', $quote->id)->count());
    }
}
