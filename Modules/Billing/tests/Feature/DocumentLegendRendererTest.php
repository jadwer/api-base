<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\DocumentLegend;
use Modules\Billing\Services\DocumentLegendRenderer;
use Tests\TestCase;

class DocumentLegendRendererTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentLegendRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new DocumentLegendRenderer();
    }

    /** @test */
    public function it_returns_null_when_no_legend_exists(): void
    {
        $this->assertNull($this->renderer->render('quote', ['folio' => 'COT-1']));
    }

    /** @test */
    public function it_returns_null_when_legend_is_inactive(): void
    {
        DocumentLegend::factory()->forType('quote')->inactive()->create();

        $this->assertNull($this->renderer->render('quote', []));
    }

    /** @test */
    public function it_substitutes_known_placeholders(): void
    {
        DocumentLegend::factory()->forType('quote')->create([
            'body' => 'Documento {folio} por {total} a nombre de {cliente}.',
        ]);

        $lines = $this->renderer->render('quote', [
            'folio' => 'COT-26000009',
            'total' => '$1,160.00 MXN',
            'cliente' => 'ACME SA',
        ]);

        $this->assertEquals(['Documento COT-26000009 por $1,160.00 MXN a nombre de ACME SA.'], $lines);
    }

    /** @test */
    public function unknown_placeholder_is_left_untouched(): void
    {
        DocumentLegend::factory()->forType('quote')->create([
            'body' => 'Folio {folio} con {placeholder_inventado}.',
        ]);

        $lines = $this->renderer->render('quote', ['folio' => 'X-1']);

        $this->assertEquals(['Folio X-1 con {placeholder_inventado}.'], $lines);
    }

    /** @test */
    public function known_placeholder_without_context_value_becomes_empty(): void
    {
        DocumentLegend::factory()->forType('quote')->create([
            'body' => 'Vence: {fecha_vencimiento}|',
        ]);

        $lines = $this->renderer->render('quote', []);

        $this->assertEquals(['Vence: |'], $lines);
    }

    /** @test */
    public function it_splits_body_into_trimmed_lines_skipping_empties(): void
    {
        DocumentLegend::factory()->forType('remission')->create([
            'body' => "Primera linea\n\n  Segunda linea  \n",
        ]);

        $lines = $this->renderer->render('remission', []);

        $this->assertEquals(['Primera linea', 'Segunda linea'], $lines);
    }

    /** @test */
    public function it_only_matches_the_requested_document_type(): void
    {
        DocumentLegend::factory()->forType('cfdi_invoice')->create(['body' => 'Solo facturas']);

        $this->assertNull($this->renderer->render('quote', []));
        $this->assertEquals(['Solo facturas'], $this->renderer->render('cfdi_invoice', []));
    }

    /** @test */
    public function placeholder_catalog_lists_every_supported_placeholder(): void
    {
        $catalog = collect($this->renderer->placeholderCatalog())->pluck('placeholder')->all();

        foreach (array_keys(DocumentLegendRenderer::PLACEHOLDERS) as $key) {
            $this->assertContains('{' . $key . '}', $catalog);
        }
    }
}
