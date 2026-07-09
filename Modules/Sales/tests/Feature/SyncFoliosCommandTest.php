<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\InvoiceSeries;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Tests for system:sync-folios.
 *
 * Scenario: after importing historical data (tenant cutover) the
 * folio_sequences table keeps current_sequence = 0 while real documents
 * already carry issued folios. The command must raise current_sequence
 * so the next getNextFolio() does not collide.
 */
class SyncFoliosCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_syncs_quote_sequence_and_next_folio_does_not_collide(): void
    {
        // Migration seeds quote sequence at 0 (prefix COT-, year yy, padding 6)
        $sequence = FolioSequence::where('document_type', 'quote')->first();
        $this->assertNotNull($sequence);
        $this->assertSame(0, $sequence->current_sequence);

        $year = now()->format('y');

        // Imported quotes with folios in the configured format (max = 7)
        Quote::factory()->create(['quote_number' => "COT-{$year}000003"]);
        Quote::factory()->create(['quote_number' => "COT-{$year}000007"]);
        // Folio with a foreign format must be ignored
        Quote::factory()->create(['quote_number' => 'LEGACY-999999']);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(7, $sequence->fresh()->current_sequence);

        // Next folio = max + 1, no collision
        $next = FolioSequence::getNextFolio('quote');
        $this->assertSame("COT-{$year}000008", $next);
        $this->assertSame(0, Quote::where('quote_number', $next)->count());
    }

    /** @test */
    public function it_syncs_sales_order_sequence_from_order_number(): void
    {
        $year = now()->format('y');

        SalesOrder::factory()->create(['order_number' => "OV-{$year}000010"]);
        SalesOrder::factory()->create(['order_number' => "OV-{$year}000004"]);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $sequence = FolioSequence::where('document_type', 'sales_order')->first();
        $this->assertSame(10, $sequence->current_sequence);
        $this->assertSame("OV-{$year}000011", FolioSequence::getNextFolio('sales_order'));
    }

    /** @test */
    public function it_never_decrements_a_sequence_already_ahead(): void
    {
        $year = now()->format('y');

        FolioSequence::where('document_type', 'quote')->update(['current_sequence' => 50]);
        Quote::factory()->create(['quote_number' => "COT-{$year}000003"]);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(
            50,
            FolioSequence::where('document_type', 'quote')->first()->current_sequence
        );
    }

    /** @test */
    public function dry_run_does_not_modify_anything(): void
    {
        $year = now()->format('y');
        Quote::factory()->create(['quote_number' => "COT-{$year}000005"]);

        // Without --force the command must behave as dry-run (safe default)
        $this->artisan('system:sync-folios')->assertSuccessful();

        $this->assertSame(
            0,
            FolioSequence::where('document_type', 'quote')->first()->current_sequence
        );

        // Explicit --dry-run behaves the same
        $this->artisan('system:sync-folios', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(
            0,
            FolioSequence::where('document_type', 'quote')->first()->current_sequence
        );
    }

    /** @test */
    public function it_parses_folios_with_four_digit_year_format(): void
    {
        FolioSequence::where('document_type', 'quote')->update([
            'year_format' => 'Y',
            'separator' => '-',
            'prefix' => 'COT',
        ]);

        $fullYear = now()->format('Y');

        Quote::factory()->create(['quote_number' => "COT-{$fullYear}-000012"]);
        // Old year folios also count when the sequence is not reset_yearly
        Quote::factory()->create(['quote_number' => 'COT-2020-000009']);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $sequence = FolioSequence::where('document_type', 'quote')->first();
        $this->assertSame(12, $sequence->current_sequence);
        $this->assertSame("COT-{$fullYear}-000013", FolioSequence::getNextFolio('quote'));
    }

    /** @test */
    public function reset_yearly_sequences_only_count_current_year_folios(): void
    {
        FolioSequence::where('document_type', 'quote')->update([
            'reset_yearly' => true,
            'last_reset_year' => (int) now()->format('Y'),
        ]);

        $year = now()->format('y');

        Quote::factory()->create(['quote_number' => "COT-{$year}000002"]);
        // Previous year folio with a higher number must NOT win
        $oldYear = now()->subYear()->format('y');
        Quote::factory()->create(['quote_number' => "COT-{$oldYear}000099"]);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $sequence = FolioSequence::where('document_type', 'quote')->first();
        $this->assertSame(2, $sequence->current_sequence);
        $this->assertSame((int) now()->format('Y'), $sequence->last_reset_year);
        $this->assertSame("COT-{$year}000003", FolioSequence::getNextFolio('quote'));
    }

    /** @test */
    public function it_reports_sequences_without_source_and_leaves_them_untouched(): void
    {
        // invoice, invoice_online and invoice_refac are seeded but no code
        // consumes them (CFDI emission uses invoice_series in Billing)
        $this->artisan('system:sync-folios', ['--force' => true])
            ->expectsOutputToContain('sin fuente')
            ->assertSuccessful();

        foreach (['invoice', 'invoice_online', 'invoice_refac'] as $type) {
            $this->assertSame(
                0,
                FolioSequence::where('document_type', $type)->first()->current_sequence,
                "Sequence {$type} should remain untouched"
            );
        }
    }

    /** @test */
    public function it_syncs_invoice_series_current_folio_against_cfdi_invoices(): void
    {
        $companySetting = CompanySetting::factory()->create();

        $series = InvoiceSeries::create([
            'company_setting_id' => $companySetting->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'current_folio' => 0,
            'folio_padding' => 6,
            'include_year' => false,
            'separator' => '-',
            'is_active' => true,
        ]);

        CFDIInvoice::factory()->create([
            'company_setting_id' => $companySetting->id,
            'series' => 'FAC',
            'folio' => 5,
        ]);
        CFDIInvoice::factory()->create([
            'company_setting_id' => $companySetting->id,
            'series' => 'FAC',
            'folio' => 9,
        ]);
        // Different series code must not affect FAC
        CFDIInvoice::factory()->create([
            'company_setting_id' => $companySetting->id,
            'series' => 'FAC-W',
            'folio' => 100,
        ]);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(9, $series->fresh()->current_folio);

        // Next folio = max + 1, formatted per series config
        $this->assertSame('FAC-000010', $series->fresh()->getNextFolio());
    }

    /** @test */
    public function it_never_decrements_invoice_series_already_ahead(): void
    {
        $companySetting = CompanySetting::factory()->create();

        $series = InvoiceSeries::create([
            'company_setting_id' => $companySetting->id,
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'cfdi_type' => 'I',
            'current_folio' => 40,
            'is_active' => true,
        ]);

        CFDIInvoice::factory()->create([
            'company_setting_id' => $companySetting->id,
            'series' => 'FAC',
            'folio' => 3,
        ]);

        $this->artisan('system:sync-folios', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(40, $series->fresh()->current_folio);
    }
}
