<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Billing\Models\InvoiceSeries;
use Modules\Billing\Models\CompanySetting;

/**
 * SA-M011: Tests for Invoice Series (FAC, FAC-W, REFAC, N) functionality
 */
class InvoiceSeriesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected CompanySetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = CompanySetting::factory()->create([
            'company_name' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO010101ABC',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_invoice_series(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('invoice-series')
            ->withData([
                'type' => 'invoice-series',
                'attributes' => [
                    'code' => 'FAC',
                    'name' => 'Factura Normal',
                    'description' => 'Serie para facturacion general',
                    'cfdiType' => 'I',
                    'initialFolio' => 1,
                    'folioPadding' => 6,
                    'includeYear' => true,
                    'yearFormat' => 'y',
                    'separator' => '-',
                    'isActive' => true,
                    'isDefault' => true,
                ],
                'relationships' => [
                    'companySetting' => [
                        'data' => [
                            'type' => 'company-settings',
                            'id' => (string) $this->settings->id,
                        ],
                    ],
                ],
            ])
            ->post('/api/v1/invoice-series')
            ->assertCreated();

        $series = InvoiceSeries::first();
        $this->assertNotNull($series);
        $this->assertEquals('FAC', $series->code);
        $this->assertEquals('I', $series->cfdi_type);
        $this->assertTrue($series->is_default);
    }

    /** @test */
    public function it_can_initialize_default_series(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/invoice-series/initialize-defaults', [
                'company_setting_id' => $this->settings->id,
            ]);

        $response->assertStatus(200);

        $series = InvoiceSeries::where('company_setting_id', $this->settings->id)->get();

        // Should have created FAC, FAC-W, REFAC, N
        $this->assertGreaterThanOrEqual(4, $series->count());
        $this->assertTrue($series->contains('code', 'FAC'));
        $this->assertTrue($series->contains('code', 'FAC-W'));
        $this->assertTrue($series->contains('code', 'REFAC'));
        $this->assertTrue($series->contains('code', 'N'));
    }

    /** @test */
    public function it_can_get_available_series_by_cfdi_type(): void
    {
        $admin = $this->getAdminUser();

        // Create series
        InvoiceSeries::initializeDefaults($this->settings);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/invoice-series/available?cfdi_type=I');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.cfdi_type', 'I');

        $data = $response->json('data');
        // All series returned should be for Ingreso (I)
        foreach ($data as $item) {
            // The previewNextFolio should be present
            $this->assertArrayHasKey('next_folio_preview', $item);
        }
    }

    /** @test */
    public function it_generates_sequential_folios(): void
    {
        $series = InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'current_folio' => 0,
            'initial_folio' => 1,
            'folio_padding' => 6,
            'include_year' => true,
            'year_format' => 'y',
            'separator' => '-',
            'is_active' => true,
        ]);

        $year = now()->format('y');

        $folio1 = $series->getNextFolio();
        $folio2 = $series->getNextFolio();
        $folio3 = $series->getNextFolio();

        $this->assertEquals("FAC-{$year}-000001", $folio1);
        $this->assertEquals("FAC-{$year}-000002", $folio2);
        $this->assertEquals("FAC-{$year}-000003", $folio3);
    }

    /** @test */
    public function it_can_preview_next_folio(): void
    {
        $admin = $this->getAdminUser();

        $series = InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'current_folio' => 5,
            'initial_folio' => 1,
            'folio_padding' => 6,
            'include_year' => false,
            'separator' => '',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/invoice-series/{$series->id}/preview-next-folio");

        $response->assertStatus(200);
        $response->assertJsonPath('data.current_folio', 5);
        $response->assertJsonPath('data.next_folio_preview', 'FAC000006');
    }

    /** @test */
    public function it_can_set_initial_folio(): void
    {
        $admin = $this->getAdminUser();

        $series = InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'current_folio' => 0,
            'initial_folio' => 1,
            'folio_padding' => 6,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/invoice-series/{$series->id}/set-initial-folio", [
                'folio' => 100,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.initial_folio', 100);

        $series->refresh();
        $this->assertEquals(100, $series->initial_folio);
        $this->assertEquals(99, $series->current_folio);
    }

    /** @test */
    public function it_can_set_series_as_default(): void
    {
        $admin = $this->getAdminUser();

        // Create two series
        $series1 = InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'is_default' => true,
            'is_active' => true,
        ]);

        $series2 = InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC-W',
            'name' => 'Factura Web',
            'cfdi_type' => 'I',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/invoice-series/{$series2->id}/set-as-default");

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_default', true);

        $series1->refresh();
        $series2->refresh();

        $this->assertFalse($series1->is_default);
        $this->assertTrue($series2->is_default);
    }

    /** @test */
    public function it_returns_series_summary(): void
    {
        $admin = $this->getAdminUser();

        InvoiceSeries::initializeDefaults($this->settings);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/invoice-series/summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_series',
                'active_series',
                'by_cfdi_type',
                'series',
            ],
        ]);
    }

    /** @test */
    public function it_filters_series_by_source_type(): void
    {
        $admin = $this->getAdminUser();

        InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'source_type' => null,
            'is_active' => true,
        ]);

        InvoiceSeries::create([
            'company_setting_id' => $this->settings->id,
            'code' => 'FAC-W',
            'name' => 'Factura Web',
            'cfdi_type' => 'I',
            'source_type' => 'web',
            'is_active' => true,
        ]);

        // Requesting for 'web' source should return both (FAC has null = any, FAC-W has web)
        // Pass company_setting_id explicitly to match our test data
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/invoice-series/available?cfdi_type=I&source=web&company_setting_id=' . $this->settings->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function series_code_must_be_uppercase(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('invoice-series')
            ->withData([
                'type' => 'invoice-series',
                'attributes' => [
                    'code' => 'fac',
                    'name' => 'Factura Normal',
                    'cfdiType' => 'I',
                ],
                'relationships' => [
                    'companySetting' => [
                        'data' => [
                            'type' => 'company-settings',
                            'id' => (string) $this->settings->id,
                        ],
                    ],
                ],
            ])
            ->post('/api/v1/invoice-series')
            ->assertStatus(422);
    }
}
