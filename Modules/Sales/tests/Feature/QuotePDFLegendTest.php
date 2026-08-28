<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\DocumentLegend;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Services\QuotePDFGenerator;
use Tests\TestCase;

/**
 * La leyenda configurable entra al PDF de cotizacion via el bloque de
 * condiciones, y sin leyenda el fallback historico sigue intacto.
 */
class QuotePDFLegendTest extends TestCase
{
    use RefreshDatabase;

    protected Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $contact = Contact::factory()->create([
            'name' => 'Cliente Leyenda',
            'tax_id' => 'XAXX010101000',
        ]);

        $this->quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'COT-26000077',
            'status' => 'draft',
            'quote_date' => now(),
            'valid_until' => now()->addDays(15),
            'subtotal_amount' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00,
            'currency' => 'MXN',
        ]);
    }

    protected function preparedConditions(): array
    {
        $generator = new QuotePDFGenerator();
        $method = new \ReflectionMethod($generator, 'prepareData');
        $method->setAccessible(true);
        $data = $method->invoke($generator, $this->quote->fresh(), []);

        return $data['conditions'];
    }

    /** @test */
    public function legend_replaces_conditions_with_resolved_placeholders(): void
    {
        DocumentLegend::factory()->forType('quote')->create([
            'body' => "Cotizacion {folio} de {cliente}.\nTotal: {total}.",
        ]);

        $conditions = $this->preparedConditions();

        $this->assertEquals([
            'Cotizacion COT-26000077 de Cliente Leyenda.',
            'Total: $116.00 MXN.',
        ], $conditions);
    }

    /** @test */
    public function inactive_legend_falls_back_to_commercial_conditions(): void
    {
        // El seeder de tests puede traer un CompanySetting activo; el generador
        // lee getActive()->first(), asi que se edita ese (o se crea si no hay)
        $settings = CompanySetting::getActive() ?? CompanySetting::create([
            'company_name' => 'Empresa Test',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'is_active' => true,
        ]);
        $settings->commercial_conditions = ['Condicion historica unica'];
        $settings->save();

        DocumentLegend::factory()->forType('quote')->inactive()->create([
            'body' => 'No deberia imprimirse',
        ]);

        $this->assertEquals(['Condicion historica unica'], $this->preparedConditions());
    }

    /** @test */
    public function without_legend_or_settings_defaults_still_apply(): void
    {
        $conditions = $this->preparedConditions();

        $this->assertNotEmpty($conditions);
        $this->assertStringContainsString('moneda nacional', strtolower(implode(' ', $conditions)));
    }

    /** @test */
    public function pdf_generates_without_error_when_legend_is_active(): void
    {
        DocumentLegend::factory()->forType('quote')->create([
            'body' => 'Documento {folio}: {total_letra}. Placeholder raro {sin_definir} intacto.',
        ]);

        $path = (new QuotePDFGenerator())->generate($this->quote);

        Storage::disk('public')->assertExists($path);
    }
}
