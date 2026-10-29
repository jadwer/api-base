<?php

namespace Modules\Reports\Services\AgingReports;

use Carbon\Carbon;
use Modules\Finance\Models\ARInvoice;

class ARAgingReportService
{
    private const EXCLUDED_STATUSES = ['paid', 'draft', 'void', 'voided', 'cancelled'];

    /**
     * Generate AR Aging Report
     *
     * @param Carbon $asOfDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $asOfDate, string $currency = 'MXN'): array
    {
        // Solo facturas vivas con saldo en la moneda pedida (antes contaba
        // borradores, anuladas y canceladas y mezclaba monedas).
        $invoices = ARInvoice::whereNotIn('status', self::EXCLUDED_STATUSES)
            ->where('invoice_date', '<=', $asOfDate)
            ->where(fn ($q) => $q->where('currency', $currency)->orWhereNull('currency'))
            ->with('customer')
            ->get();

        $agingBuckets = [
            'current' => [],      // por vencer o hasta 30 dias de atraso
            'days_31_60' => [],   // 31-60 days
            'days_61_90' => [],   // 61-90 days
            'over_90' => [],      // 90+ days
        ];

        $totals = [
            'current' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
        ];

        foreach ($invoices as $invoice) {
            // Dias de atraso contra el vencimiento. Carbon 3 devuelve diffInDays
            // con signo: el calculo anterior (fecha de corte contra fecha de
            // factura) daba negativo y todo caia en "corriente".
            $dueDate = Carbon::parse($invoice->due_date ?? $invoice->invoice_date);
            $daysOutstanding = max(0, (int) floor($dueDate->diffInDays($asOfDate, false)));
            $remainingBalance = $invoice->total_amount - $invoice->paid_amount;

            if ($remainingBalance <= 0.01) {
                continue;
            }

            $invoiceData = [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'total_amount' => round($invoice->total_amount, 2),
                'paid_amount' => round($invoice->paid_amount, 2),
                'remaining_balance' => round($remainingBalance, 2),
                'days_outstanding' => $daysOutstanding,
            ];

            if ($daysOutstanding <= 30) {
                $agingBuckets['current'][] = $invoiceData;
                $totals['current'] += $remainingBalance;
            } elseif ($daysOutstanding <= 60) {
                $agingBuckets['days_31_60'][] = $invoiceData;
                $totals['days_31_60'] += $remainingBalance;
            } elseif ($daysOutstanding <= 90) {
                $agingBuckets['days_61_90'][] = $invoiceData;
                $totals['days_61_90'] += $remainingBalance;
            } else {
                $agingBuckets['over_90'][] = $invoiceData;
                $totals['over_90'] += $remainingBalance;
            }
        }

        $grandTotal = array_sum($totals);

        return [
            'asOfDate' => $asOfDate->format('Y-m-d'),
            'currency' => $currency,
            'agingBuckets' => $agingBuckets,
            'totals' => [
                'current' => round($totals['current'], 2),
                'days_31_60' => round($totals['days_31_60'], 2),
                'days_61_90' => round($totals['days_61_90'], 2),
                'over_90' => round($totals['over_90'], 2),
                'total' => round($grandTotal, 2),
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
