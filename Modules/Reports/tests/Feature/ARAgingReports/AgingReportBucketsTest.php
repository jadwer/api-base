<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Carbon\Carbon;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\ARInvoice;
use Modules\Reports\Services\AgingReports\APAgingReportService;
use Modules\Reports\Services\AgingReports\ARAgingReportService;
use Tests\TestCase;

/**
 * Auditoria finanzas 2026-10, punto 10: el reporte de antiguedad de la UI
 * media contra la fecha de factura con diffInDays con signo (Carbon 3) y
 * todo caia en "corriente"; ademas contaba anuladas, borradores y otras
 * monedas.
 */
class AgingReportBucketsTest extends TestCase
{
    private function ar(array $overrides): ARInvoice
    {
        return ARInvoice::factory()->create(array_merge([
            'contact_id' => Contact::factory()->customer()->create()->id,
            'invoice_number' => 'AGE-' . uniqid(),
            'invoice_date' => Carbon::parse('2026-06-01'),
            'due_date' => Carbon::parse('2026-07-01'),
            'currency' => 'MXN',
            'total_amount' => 1000,
            'paid_amount' => 0,
            'status' => 'posted',
        ], $overrides));
    }

    private function numbers(array $bucket): array
    {
        return array_column($bucket, 'invoice_number');
    }

    public function test_ar_buckets_by_days_past_due_and_excludes_dead_invoices(): void
    {
        $asOf = Carbon::parse('2026-08-15'); // 45 dias despues del vencimiento
        $late = $this->ar([]);
        $notDue = $this->ar(['due_date' => Carbon::parse('2026-09-01')]);
        $veryLate = $this->ar(['due_date' => Carbon::parse('2026-04-01'), 'invoice_date' => Carbon::parse('2026-03-01')]);
        $voided = $this->ar(['status' => 'voided']);
        $draft = $this->ar(['status' => 'draft']);
        $usd = $this->ar(['currency' => 'USD']);

        $report = app(ARAgingReportService::class)->generate($asOf, 'MXN');
        $all = array_merge(...array_values(array_map(fn ($b) => $this->numbers($b), $report['agingBuckets'])));

        $this->assertContains($late->invoice_number, $this->numbers($report['agingBuckets']['days_31_60']));
        $this->assertContains($notDue->invoice_number, $this->numbers($report['agingBuckets']['current']));
        $this->assertContains($veryLate->invoice_number, $this->numbers($report['agingBuckets']['over_90']));
        foreach ([$voided, $draft, $usd] as $excluded) {
            $this->assertNotContains($excluded->invoice_number, $all);
        }
    }

    public function test_ap_uses_the_same_rules(): void
    {
        $invoice = APInvoice::factory()->create([
            'contact_id' => Contact::factory()->supplier()->create()->id,
            'invoice_number' => 'AGEP-' . uniqid(),
            'invoice_date' => Carbon::parse('2026-06-01'),
            'due_date' => Carbon::parse('2026-07-01'),
            'currency' => 'MXN',
            'total_amount' => 1000,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        $report = app(APAgingReportService::class)->generate(Carbon::parse('2026-08-15'), 'MXN');

        $this->assertContains($invoice->invoice_number, $this->numbers($report['agingBuckets']['days_31_60']));
    }
}
