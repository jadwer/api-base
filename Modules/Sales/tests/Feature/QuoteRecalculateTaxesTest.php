<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Tests\TestCase;

/**
 * POST /quotes/{id}/recalculate-taxes (junta 30-10): cotizaciones capturadas
 * antes del IVA 16 % del catalogo guardaron tasa 0 en sus partidas.
 */
class QuoteRecalculateTaxesTest extends TestCase
{
    /** @return array{0: Quote, 1: QuoteItem} */
    private function quoteWithStaleTax(string $status = 'draft'): array
    {
        $quote = Quote::factory()->create([
            'contact_id' => Contact::factory()->customer()->create()->id,
            'status' => $status,
        ]);
        $product = Product::factory()->create(['tax_rate' => 16, 'iva' => true]);
        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'quoted_price' => 100,
            'discount_percentage' => 0,
            'tax_rate' => 0,
        ]);

        return [$quote, $item];
    }

    public function test_draft_quote_takes_current_product_tax(): void
    {
        [$quote, $item] = $this->quoteWithStaleTax();
        $this->assertEquals(0, (float) $quote->fresh()->tax_amount);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/recalculate-taxes")
            ->assertOk()
            ->assertJson(['message' => 'Impuestos recalculados en 1 partida.']);

        $this->assertEquals(16, (float) $item->fresh()->tax_rate);
        $this->assertEquals(32, (float) $item->fresh()->tax_amount);
        $this->assertEquals(32, (float) $quote->fresh()->tax_amount);
        $this->assertEquals(232, (float) $quote->fresh()->total_amount);

        // Segunda corrida: nada que cambiar.
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/recalculate-taxes")
            ->assertOk()
            ->assertJson(['message' => 'Los impuestos ya estaban al día.']);
    }

    public function test_sent_quote_is_rejected_in_spanish(): void
    {
        [$quote, $item] = $this->quoteWithStaleTax('sent');

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/recalculate-taxes")
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Solo se pueden recalcular los impuestos de una cotización en borrador.');

        $this->assertEquals(0, (float) $item->fresh()->tax_rate);
    }

    public function test_guest_cannot_recalculate(): void
    {
        [$quote] = $this->quoteWithStaleTax();

        $this->postJson("/api/v1/quotes/{$quote->id}/recalculate-taxes")->assertStatus(401);
    }

    public function test_duplicate_takes_current_product_tax(): void
    {
        [$quote] = $this->quoteWithStaleTax('sent');

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate")
            ->assertStatus(201);

        $copy = Quote::findOrFail($response->json('data.id'));
        $this->assertEquals(16, (float) $copy->items()->first()->tax_rate);
        $this->assertEquals(32, (float) $copy->fresh()->tax_amount);
    }
}
