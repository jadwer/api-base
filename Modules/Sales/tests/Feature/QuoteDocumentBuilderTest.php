<?php

namespace Modules\Sales\Tests\Feature;

use Atomo\Documents\Contracts\PdfRenderer;
use Atomo\Documents\Layout\Layouts;
use Atomo\Documents\Testing\FakePdfRenderer;
use Modules\AppConfig\Models\AppSetting;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Sales\Support\Documents\QuoteDocumentData;
use Tests\TestCase;

/**
 * Cotizacion con el constructor de documentos de Atomo (12-11-2026): el
 * preajuste de LWM es valido, el `$doc` cumple el contrato y, con el
 * interruptor documents.builder.quote encendido, el PDF sale por bloques;
 * apagado, por la plantilla Blade de siempre.
 */
class QuoteDocumentBuilderTest extends TestCase
{
    private function quote(bool $pending = false): Quote
    {
        $contact = Contact::factory()->customer()->create(['name' => 'Farmabiot SA', 'email' => 'compras@farmabiot.mx']);
        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'quote_number' => 'COT-26009001', 'valid_until' => now()->addDays(30)->toDateString(), 'currency' => 'MXN']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'product_name' => 'Caldo Eugon LT', 'quantity' => 2, 'unit_price' => 100, 'quoted_price' => $pending ? 0 : 100]);

        return $quote->fresh();
    }

    public function test_el_preajuste_de_lwm_es_un_layout_v1_valido(): void
    {
        $this->assertTrue(Layouts::has('lwm-cotizacion'));
        // Layouts::get normaliza (truena con la ruta si el JSON trae una llave desconocida).
        $layout = Layouts::get('lwm-cotizacion');
        $this->assertSame(1, $layout['version']);
        $this->assertSame('color', $layout['theme']['mode']);
    }

    public function test_el_doc_trae_emisor_folio_receptor_partidas_y_totales(): void
    {
        $quote = $this->quote();
        $prepared = (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class));
        $doc = QuoteDocumentData::from($quote, $prepared);

        $this->assertSame('COT-26009001', $doc['title']['number']);
        $this->assertStringStartsWith('Vigencia: ', $doc['title']['chip']);
        $this->assertSame('Farmabiot SA', $doc['party']['name']);
        $this->assertSame('Caldo Eugon LT', $doc['lines'][0]['description']);
        $this->assertEquals(100.0, $doc['lines'][0]['unitPrice']);
        $this->assertSame('MXN', $doc['totals']['currency']);
        $this->assertNotEmpty($doc['issuer']['name']);
    }

    public function test_una_partida_a_granel_sin_precio_pinta_por_confirmar_y_nunca_cero(): void
    {
        $quote = $this->quote(pending: true);
        $prepared = (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class));
        $doc = QuoteDocumentData::from($quote, $prepared);

        $this->assertNull($doc['lines'][0]['unitPrice']);
        $this->assertNull($doc['lines'][0]['total']);
        $this->assertSame('Por confirmar', $doc['lines'][0]['priceNote']);
        $this->assertNotEmpty($doc['notices']);
    }

    public function test_con_el_interruptor_encendido_el_pdf_sale_por_bloques(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        AppSetting::set('documents.builder.quote', true, 'documents', 'boolean');
        $quote = $this->quote();

        $bytes = app(QuotePDFGenerator::class)->render($quote, (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class)));

        $this->assertNotSame('', $bytes);
        $this->assertStringContainsString('COT-26009001', $fake->lastHtml());
        $this->assertStringContainsString('Caldo Eugon LT', $fake->lastHtml());
    }

    public function test_apagado_sigue_la_plantilla_de_siempre(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        $quote = $this->quote();

        $bytes = app(QuotePDFGenerator::class)->render($quote, (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class)));

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertFalse(QuotePDFGenerator::builderEnabled());
    }

    public function test_la_cotizacion_del_carrito_sale_por_bloques_con_su_interruptor(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        AppSetting::set('documents.builder.cart_quote', true, 'documents', 'boolean');
        $this->assertTrue(Layouts::has('lwm-cotizacion-carrito'));

        $lines = [
            ['sku' => 'SKU-1', 'name' => 'Reactivo A', 'brand' => 'Marca', 'unit' => 'Pieza', 'quantity' => 2, 'unit_price' => 50.0, 'net' => 100.0, 'tax' => 16.0, 'total' => 116.0, 'price_pending' => false],
            ['sku' => 'SKU-2', 'name' => 'Granel B', 'brand' => null, 'unit' => 'Litro', 'quantity' => 1, 'unit_price' => null, 'net' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'price_pending' => true],
        ];
        app(\Modules\Sales\Services\CartQuotePdfService::class)->render($lines, ['net' => 100.0, 'tax' => 16.0, 'total' => 116.0], 1);

        $html = $fake->lastHtml();
        $this->assertStringContainsString('Cotización informativa', $html);
        $this->assertStringContainsString('Reactivo A', $html);
        $this->assertStringContainsString('Por confirmar', $html);
        $this->assertStringNotContainsString('Receptor', $html);
    }
}
