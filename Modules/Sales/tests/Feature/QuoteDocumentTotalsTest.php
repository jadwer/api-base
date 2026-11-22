<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Mail\QuoteSentMail;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Services\QuotePDFGenerator;
use Tests\TestCase;

/**
 * `quotes.subtotal_amount` ya viene neto de descuentos. El PDF, el constructor de documentos
 * y los correos pintaban ese subtotal y debajo un renglon "Descuento": se leia como si el
 * descuento se restara dos veces y los renglones no sumaban el total (BUG-02, 22-11).
 */
class QuoteDocumentTotalsTest extends TestCase
{
    private function quoteWithDiscount(): Quote
    {
        $quote = Quote::factory()->create(['contact_id' => Contact::factory()->customer()->create()->id]);
        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'unit_price' => 100,
            'quoted_price' => 100,
            'discount_percentage' => 10,
        ]);

        return $quote->refresh();
    }

    public function test_gross_subtotal_is_the_amount_before_discounts(): void
    {
        $quote = $this->quoteWithDiscount();

        $this->assertEqualsWithDelta(180, (float) $quote->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(20, (float) $quote->discount_amount, 0.001);
        $this->assertEqualsWithDelta(200, $quote->grossSubtotal(), 0.001);
    }

    public function test_pdf_totals_add_up_to_the_total(): void
    {
        $quote = $this->quoteWithDiscount();
        $method = new \ReflectionMethod(QuotePDFGenerator::class, 'calculateTotals');
        $totals = $method->invoke(app(QuotePDFGenerator::class), $quote);

        $this->assertEqualsWithDelta(200, (float) $totals['subtotal'], 0.001);
        $this->assertEqualsWithDelta(
            (float) $totals['total'],
            (float) $totals['subtotal'] - (float) $totals['discount'] + (float) $totals['tax'],
            0.01
        );
    }

    public function test_sent_mail_summary_adds_up_to_the_total(): void
    {
        $quote = $this->quoteWithDiscount();
        $summary = (new QuoteSentMail($quote))->quoteSummary;

        $this->assertEqualsWithDelta(200, (float) $summary['subtotal'], 0.001);
        $this->assertEqualsWithDelta(
            (float) $summary['total'],
            (float) $summary['subtotal'] - (float) $summary['discount'] + (float) $summary['tax'],
            0.01
        );
    }
}
