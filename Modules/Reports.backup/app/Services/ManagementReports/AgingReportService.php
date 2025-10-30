<?php

namespace Modules\Reports\Services\ManagementReports;

use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Carbon\Carbon;

class AgingReportService
{
    /**
     * Generate Accounts Receivable Aging Report
     *
     * @param Carbon $asOfDate
     * @return array
     */
    public function generateAR(Carbon $asOfDate): array
    {
        $invoices = ARInvoice::where('status', '!=', 'draft')
            ->where('status', '!=', 'voided')
            ->whereDate('invoice_date', '<=', $asOfDate->toDateString())
            ->with('customer')
            ->get();

        $customerAging = [];
        $summary = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $remainingBalance = $invoice->remainingBalance ?? ($invoice->total_amount - $invoice->paid_amount);

            if ($remainingBalance <= 0) {
                continue; // Skip fully paid invoices
            }

            $daysOverdue = $asOfDate->diffInDays(Carbon::parse($invoice->due_date), false);
            $bucket = $this->getAgingBucket($daysOverdue);

            $customerId = $invoice->customer_id;
            $customerName = $invoice->customer->name ?? 'Unknown Customer';

            if (!isset($customerAging[$customerId])) {
                $customerAging[$customerId] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'days_over_90' => 0,
                    'total' => 0,
                ];
            }

            $customerAging[$customerId][$bucket] += $remainingBalance;
            $customerAging[$customerId]['total'] += $remainingBalance;
            $summary[$bucket] += $remainingBalance;
            $summary['total'] += $remainingBalance;
        }

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'report_type' => 'accounts_receivable',
            'currency' => config('app.currency', 'MXN'),
            'customers' => array_values($customerAging),
            'summary' => $summary,
        ];
    }

    /**
     * Generate Accounts Payable Aging Report
     *
     * @param Carbon $asOfDate
     * @return array
     */
    public function generateAP(Carbon $asOfDate): array
    {
        $invoices = APInvoice::where('status', '!=', 'draft')
            ->where('status', '!=', 'voided')
            ->whereDate('invoice_date', '<=', $asOfDate->toDateString())
            ->with('supplier')
            ->get();

        $supplierAging = [];
        $summary = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $remainingBalance = $invoice->remainingBalance ?? ($invoice->total_amount - $invoice->paid_amount);

            if ($remainingBalance <= 0) {
                continue; // Skip fully paid invoices
            }

            $daysOverdue = $asOfDate->diffInDays(Carbon::parse($invoice->due_date), false);
            $bucket = $this->getAgingBucket($daysOverdue);

            $supplierId = $invoice->supplier_id;
            $supplierName = $invoice->supplier->name ?? 'Unknown Supplier';

            if (!isset($supplierAging[$supplierId])) {
                $supplierAging[$supplierId] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplierName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'days_over_90' => 0,
                    'total' => 0,
                ];
            }

            $supplierAging[$supplierId][$bucket] += $remainingBalance;
            $supplierAging[$supplierId]['total'] += $remainingBalance;
            $summary[$bucket] += $remainingBalance;
            $summary['total'] += $remainingBalance;
        }

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'report_type' => 'accounts_payable',
            'currency' => config('app.currency', 'MXN'),
            'suppliers' => array_values($supplierAging),
            'summary' => $summary,
        ];
    }

    /**
     * Determine aging bucket based on days overdue
     *
     * @param int $daysOverdue (positive = not yet due, negative = overdue)
     * @return string
     */
    private function getAgingBucket(int $daysOverdue): string
    {
        if ($daysOverdue >= 0) {
            return 'current';
        }

        $daysOverdue = abs($daysOverdue);

        if ($daysOverdue <= 30) {
            return 'days_1_30';
        } elseif ($daysOverdue <= 60) {
            return 'days_31_60';
        } elseif ($daysOverdue <= 90) {
            return 'days_61_90';
        } else {
            return 'days_over_90';
        }
    }
}
