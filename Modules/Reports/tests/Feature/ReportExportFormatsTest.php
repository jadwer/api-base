<?php

namespace Modules\Reports\Tests\Feature;

use Tests\TestCase;

/**
 * Exportacion de reportes en los tres formatos (23-10): Excel y PDF daban 500
 * porque ExportService apuntaba a clases y vistas que nunca existieron. Ahora
 * los tres salen de las mismas filas.
 */
class ReportExportFormatsTest extends TestCase
{
    private const CONTENT_TYPES = [
        'csv' => 'text/csv',
        'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf',
    ];

    /** @return array<string, array{0: string, 1: string}> */
    public static function exports(): array
    {
        $period = 'start_date=' . now()->startOfMonth()->toDateString() . '&end_date=' . now()->toDateString();
        $asOf = 'as_of_date=' . now()->toDateString();
        $cases = [];
        foreach ([
            'balance-sheet' => $asOf,
            'income-statement' => $period,
            'cash-flow' => $period,
            'trial-balance' => $asOf,
            'sales-by-customer' => $period,
            'sales-by-employee' => $period,
            'sales-by-batch' => $period,
            'sales-profitability' => $period,
            'sales-history' => $period,
            'aging-ar' => $asOf,
            'aging-ap' => $asOf,
        ] as $report => $query) {
            foreach (array_keys(self::CONTENT_TYPES) as $format) {
                $cases["{$report} {$format}"] = [$report, "format={$format}&{$query}"];
            }
        }

        return $cases;
    }

    /** @dataProvider exports */
    public function test_report_exports_in_every_format(string $report, string $query): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->get("/api/v1/reports/{$report}/export?{$query}");

        $response->assertOk();
        parse_str($query, $params);
        $this->assertStringContainsString(self::CONTENT_TYPES[$params['format']], (string) $response->headers->get('Content-Type'));
    }
}
