<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Tests\TestCase;

/**
 * Regression guard for the configurable-IVA refactor.
 *
 * QuoteItem::calculateTotals() now delegates the IVA math to the shared
 * TaxCalculator. With the tenant flag pricing.prices_include_tax = false
 * (the default and current behavior) the results must be EXACTLY what the
 * old inline math produced.
 */
class QuoteItemTaxCalculatorTest extends TestCase
{
    private function makeItem(array $attributes = []): QuoteItem
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        return QuoteItem::factory()->create(array_merge([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'quoted_price' => 100,
            'discount_percentage' => 0,
            'tax_rate' => 16,
        ], $attributes));
    }

    /** Flag false: price 100, rate 16 -> tax 16, total 116 (historical result). */
    public function test_quote_item_flag_false_adds_iva_on_top(): void
    {
        $item = $this->makeItem();

        $this->assertEqualsWithDelta(16.0, $item->tax_amount, 0.001);
        $this->assertEqualsWithDelta(116.0, $item->total, 0.001);
    }

    /** Flag false with discount reproduces the pre-refactor numbers. */
    public function test_quote_item_flag_false_with_discount(): void
    {
        // subtotal 200, 25% discount -> after 150, tax 24, total 174.
        $item = $this->makeItem([
            'quantity' => 2,
            'discount_percentage' => 25,
        ]);

        $this->assertEqualsWithDelta(50.0, $item->discount_amount, 0.001);
        $this->assertEqualsWithDelta(24.0, $item->tax_amount, 0.001);
        $this->assertEqualsWithDelta(174.0, $item->total, 0.001);
    }

    /** Exempt line (rate 0): no tax, total equals subtotal. */
    public function test_quote_item_exempt_rate_has_no_tax(): void
    {
        $item = $this->makeItem(['tax_rate' => 0]);

        $this->assertEqualsWithDelta(0.0, $item->tax_amount, 0.001);
        $this->assertEqualsWithDelta(100.0, $item->total, 0.001);
    }
}
