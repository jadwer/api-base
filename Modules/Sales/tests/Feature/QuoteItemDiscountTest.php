<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

/**
 * Descuento por monto o por porcentaje en quote items.
 *
 * Regla de precedencia: el request acepta discountPercentage O discountAmount
 * (ambos a la vez con valor = 422). El campo enviado es la fuente de verdad y
 * el modelo deriva el otro para mantenerlos consistentes.
 */
class QuoteItemDiscountTest extends TestCase
{
    private function makeItem(array $attributes = []): QuoteItem
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        return QuoteItem::factory()->create(array_merge([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'quoted_price' => 100,
            'discount_percentage' => 0,
            'tax_rate' => 16,
        ], $attributes));
    }

    public function test_discount_by_amount_derives_percentage(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem(); // subtotal = 200

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountAmount' => 50,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $item->refresh();
        $this->assertEqualsWithDelta(50.0, $item->discount_amount, 0.01);
        $this->assertEqualsWithDelta(25.0, $item->discount_percentage, 0.01);
        $this->assertEqualsWithDelta(24.0, $item->tax_amount, 0.01); // (200-50) * 16%
        $this->assertEqualsWithDelta(174.0, $item->total, 0.01);
    }

    public function test_discount_by_percentage_derives_amount(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem(); // subtotal = 200

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountPercentage' => 10,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $item->refresh();
        $this->assertEqualsWithDelta(10.0, $item->discount_percentage, 0.01);
        $this->assertEqualsWithDelta(20.0, $item->discount_amount, 0.01);
        $this->assertEqualsWithDelta(208.8, $item->total, 0.01); // (200-20) * 1.16
    }

    public function test_discount_amount_greater_than_subtotal_is_capped(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem(); // subtotal = 200

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountAmount' => 500,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $item->refresh();
        $this->assertEqualsWithDelta(200.0, $item->discount_amount, 0.01);
        $this->assertEqualsWithDelta(100.0, $item->discount_percentage, 0.01);
        $this->assertEqualsWithDelta(0.0, $item->total, 0.01);
    }

    public function test_zero_discount_amount_clears_both_fields(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem(['discount_percentage' => 25]); // amount derivado = 50

        $this->assertEqualsWithDelta(50.0, $item->refresh()->discount_amount, 0.01);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountAmount' => 0,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $item->refresh();
        $this->assertEqualsWithDelta(0.0, $item->discount_amount, 0.01);
        $this->assertEqualsWithDelta(0.0, $item->discount_percentage, 0.01);
        $this->assertEqualsWithDelta(232.0, $item->total, 0.01); // 200 * 1.16
    }

    public function test_sending_both_discount_fields_returns_422(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountPercentage' => 10,
                    'discountAmount' => 50,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertStatus(422);
    }

    public function test_can_create_quote_item_with_discount_amount(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create(['price' => 100]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quoteId' => $quote->id,
                    'productId' => $product->id,
                    'quantity' => 3,
                    'unitPrice' => 100,
                    'quotedPrice' => 100,
                    'discountAmount' => 30,
                    'taxRate' => 16,
                ],
            ])
            ->post('/api/v1/quote-items');

        $response->assertCreated();

        $item = QuoteItem::query()->where('quote_id', $quote->id)->firstOrFail();
        $this->assertEqualsWithDelta(30.0, $item->discount_amount, 0.01);
        $this->assertEqualsWithDelta(10.0, $item->discount_percentage, 0.01); // 30 / 300
        $this->assertEqualsWithDelta(313.2, $item->total, 0.01); // 270 * 1.16
    }

    public function test_can_update_quoted_price_to_zero(): void
    {
        $admin = $this->getAdminUser();
        $item = $this->makeItem();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quotedPrice' => 0,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $item->refresh();
        $this->assertEqualsWithDelta(0.0, $item->quoted_price, 0.01);
        $this->assertEqualsWithDelta(0.0, $item->total, 0.01);
    }
}
