<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;

/**
 * FI-M001: Late Payment Penalty Service
 *
 * Calculates and applies late payment penalties to overdue invoices.
 */
class LatePenaltyService
{
    /**
     * Default annual interest rate for late payments (in percentage).
     */
    protected float $defaultAnnualRate = 18.0; // 18% annual rate

    /**
     * Grace period in days before penalties start.
     */
    protected int $gracePeriodDays = 0;

    /**
     * Minimum penalty amount to apply.
     */
    protected float $minimumPenaltyAmount = 1.00;

    /**
     * Calculate the late penalty for a specific invoice.
     *
     * @param ARInvoice $invoice
     * @param Carbon|null $asOfDate Calculate as of this date (default: today)
     * @param float|null $annualRate Override the default annual rate
     * @return array
     */
    public function calculatePenalty(ARInvoice $invoice, ?Carbon $asOfDate = null, ?float $annualRate = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now();
        $rate = $annualRate ?? $this->defaultAnnualRate;

        // Check if invoice is overdue
        if (!$this->isOverdue($invoice, $asOfDate)) {
            return [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'is_overdue' => false,
                'days_overdue' => 0,
                'outstanding_amount' => $invoice->total_amount - $invoice->paid_amount,
                'penalty_amount' => 0.0,
                'annual_rate' => $rate,
                'daily_rate' => $rate / 365,
                'as_of_date' => $asOfDate->format('Y-m-d'),
                'message' => 'La factura no está vencida',
            ];
        }

        // Calculate days overdue (considering grace period)
        $dueDate = Carbon::parse($invoice->due_date);
        $effectiveDueDate = $dueDate->copy()->addDays($this->gracePeriodDays);
        $daysOverdue = max(0, $effectiveDueDate->diffInDays($asOfDate, false));

        // Calculate outstanding amount
        $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;

        // Calculate penalty using simple interest formula
        // Penalty = Principal × Rate × Time
        $dailyRate = $rate / 365 / 100;
        $penaltyAmount = $outstandingAmount * $dailyRate * $daysOverdue;

        // Apply minimum penalty
        if ($penaltyAmount > 0 && $penaltyAmount < $this->minimumPenaltyAmount) {
            $penaltyAmount = $this->minimumPenaltyAmount;
        }

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'is_overdue' => true,
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'days_overdue' => $daysOverdue,
            'outstanding_amount' => round($outstandingAmount, 2),
            'penalty_amount' => round($penaltyAmount, 2),
            'annual_rate' => $rate,
            'daily_rate' => round($dailyRate * 100, 6),
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'calculation' => [
                'formula' => 'Principal × (Rate/365) × Days',
                'principal' => round($outstandingAmount, 2),
                'rate_decimal' => round($dailyRate, 6),
                'days' => $daysOverdue,
            ],
        ];
    }

    /**
     * Check if an invoice is overdue.
     *
     * @param ARInvoice $invoice
     * @param Carbon|null $asOfDate
     * @return bool
     */
    public function isOverdue(ARInvoice $invoice, ?Carbon $asOfDate = null): bool
    {
        $asOfDate = $asOfDate ?? Carbon::now();

        // Must have outstanding balance
        if ($invoice->paid_amount >= $invoice->total_amount) {
            return false;
        }

        // Check if past due date + grace period
        $effectiveDueDate = Carbon::parse($invoice->due_date)->addDays($this->gracePeriodDays);

        return $asOfDate->greaterThan($effectiveDueDate);
    }

    /**
     * Get all overdue invoices with penalties.
     *
     * @param Carbon|null $asOfDate
     * @param float|null $annualRate
     * @return Collection
     */
    public function getOverdueInvoicesWithPenalties(?Carbon $asOfDate = null, ?float $annualRate = null): Collection
    {
        $asOfDate = $asOfDate ?? Carbon::now();
        $effectiveDate = $asOfDate->copy()->subDays($this->gracePeriodDays);

        $overdueInvoices = ARInvoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', $effectiveDate)
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->with('contact')
            ->get();

        return $overdueInvoices->map(function ($invoice) use ($asOfDate, $annualRate) {
            $penalty = $this->calculatePenalty($invoice, $asOfDate, $annualRate);
            $penalty['customer_name'] = $invoice->contact?->name ?? 'N/A';
            $penalty['customer_id'] = $invoice->contact_id;
            return $penalty;
        });
    }

    /**
     * Get penalty summary by customer.
     *
     * @param Carbon|null $asOfDate
     * @param float|null $annualRate
     * @return Collection
     */
    public function getPenaltySummaryByCustomer(?Carbon $asOfDate = null, ?float $annualRate = null): Collection
    {
        $overdueWithPenalties = $this->getOverdueInvoicesWithPenalties($asOfDate, $annualRate);

        return $overdueWithPenalties->groupBy('customer_id')->map(function ($invoices, $customerId) {
            $firstInvoice = $invoices->first();
            return [
                'customer_id' => $customerId,
                'customer_name' => $firstInvoice['customer_name'],
                'total_invoices' => $invoices->count(),
                'total_outstanding' => round($invoices->sum('outstanding_amount'), 2),
                'total_penalty' => round($invoices->sum('penalty_amount'), 2),
                'max_days_overdue' => $invoices->max('days_overdue'),
                'invoices' => $invoices->values()->all(),
            ];
        })->values();
    }

    /**
     * Apply penalty to an invoice (creates a debit adjustment).
     *
     * @param ARInvoice $invoice
     * @param float|null $penaltyAmount If null, calculates automatically
     * @param string|null $notes
     * @return array
     */
    public function applyPenalty(ARInvoice $invoice, ?float $penaltyAmount = null, ?string $notes = null): array
    {
        // Calculate penalty if not provided
        if ($penaltyAmount === null) {
            $calculation = $this->calculatePenalty($invoice);
            if (!$calculation['is_overdue']) {
                return [
                    'success' => false,
                    'message' => 'La factura no está vencida, no se puede aplicar penalidad',
                    'invoice' => $invoice,
                ];
            }
            $penaltyAmount = $calculation['penalty_amount'];
        }

        if ($penaltyAmount <= 0) {
            return [
                'success' => false,
                'message' => 'El monto de penalidad debe ser mayor a cero',
                'invoice' => $invoice,
            ];
        }

        return DB::transaction(function () use ($invoice, $penaltyAmount, $notes) {
            // Record penalty in metadata
            $existingPenalties = $invoice->metadata['penalties'] ?? [];
            $existingPenalties[] = [
                'amount' => round($penaltyAmount, 2),
                'applied_at' => now()->toIso8601String(),
                'notes' => $notes,
                'days_overdue' => Carbon::parse($invoice->due_date)->diffInDays(now()),
            ];

            // Update invoice total
            $newTotal = $invoice->total_amount + $penaltyAmount;

            $invoice->update([
                'total_amount' => $newTotal,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'penalties' => $existingPenalties,
                    'last_penalty_date' => now()->toIso8601String(),
                    'total_penalties' => round(collect($existingPenalties)->sum('amount'), 2),
                ]),
            ]);

            return [
                'success' => true,
                'message' => 'Penalidad aplicada exitosamente',
                'invoice' => $invoice->fresh(),
                'penalty_applied' => round($penaltyAmount, 2),
                'new_total' => round($newTotal, 2),
            ];
        });
    }

    /**
     * Get customer late payment history.
     *
     * @param Contact $customer
     * @return array
     */
    public function getCustomerPaymentHistory(Contact $customer): array
    {
        $invoices = ARInvoice::where('contact_id', $customer->id)
            ->orderBy('invoice_date', 'desc')
            ->get();

        $paidInvoices = $invoices->where('status', 'paid');
        $latePayments = $paidInvoices->filter(function ($invoice) {
            return $invoice->paid_date && Carbon::parse($invoice->paid_date)->greaterThan($invoice->due_date);
        });

        $currentOverdue = $invoices->filter(function ($invoice) {
            return $this->isOverdue($invoice);
        });

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $paidInvoices->count(),
            'late_payments_count' => $latePayments->count(),
            'late_payment_rate' => $paidInvoices->count() > 0
                ? round(($latePayments->count() / $paidInvoices->count()) * 100, 2)
                : 0,
            'current_overdue_invoices' => $currentOverdue->count(),
            'current_overdue_amount' => round($currentOverdue->sum(fn($inv) => $inv->total_amount - $inv->paid_amount), 2),
            'average_days_late' => $latePayments->count() > 0
                ? round($latePayments->avg(fn($inv) => Carbon::parse($inv->paid_date)->diffInDays($inv->due_date)), 1)
                : 0,
        ];
    }

    /**
     * Get aging report for overdue invoices.
     *
     * @param Carbon|null $asOfDate
     * @return array
     */
    public function getAgingReport(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now();

        $overdueInvoices = ARInvoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', $asOfDate)
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->with('contact')
            ->get();

        $buckets = [
            '1-30' => ['min' => 1, 'max' => 30, 'invoices' => collect(), 'amount' => 0],
            '31-60' => ['min' => 31, 'max' => 60, 'invoices' => collect(), 'amount' => 0],
            '61-90' => ['min' => 61, 'max' => 90, 'invoices' => collect(), 'amount' => 0],
            '91-120' => ['min' => 91, 'max' => 120, 'invoices' => collect(), 'amount' => 0],
            '120+' => ['min' => 121, 'max' => PHP_INT_MAX, 'invoices' => collect(), 'amount' => 0],
        ];

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays($asOfDate);
            $outstanding = $invoice->total_amount - $invoice->paid_amount;

            foreach ($buckets as $key => &$bucket) {
                if ($daysOverdue >= $bucket['min'] && $daysOverdue <= $bucket['max']) {
                    $bucket['invoices']->push([
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->contact?->name ?? 'N/A',
                        'due_date' => $invoice->due_date->format('Y-m-d'),
                        'days_overdue' => $daysOverdue,
                        'outstanding' => round($outstanding, 2),
                    ]);
                    $bucket['amount'] += $outstanding;
                    break;
                }
            }
        }

        // Format output
        $report = [];
        foreach ($buckets as $key => $bucket) {
            $report[] = [
                'bucket' => $key,
                'invoice_count' => $bucket['invoices']->count(),
                'total_amount' => round($bucket['amount'], 2),
                'invoices' => $bucket['invoices']->values()->all(),
            ];
        }

        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'total_overdue_invoices' => $overdueInvoices->count(),
            'total_overdue_amount' => round($overdueInvoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount), 2),
            'buckets' => $report,
        ];
    }

    /**
     * Set the annual interest rate.
     *
     * @param float $rate
     * @return self
     */
    public function setAnnualRate(float $rate): self
    {
        $this->defaultAnnualRate = $rate;
        return $this;
    }

    /**
     * Set the grace period in days.
     *
     * @param int $days
     * @return self
     */
    public function setGracePeriod(int $days): self
    {
        $this->gracePeriodDays = $days;
        return $this;
    }

    /**
     * Set the minimum penalty amount.
     *
     * @param float $amount
     * @return self
     */
    public function setMinimumPenalty(float $amount): self
    {
        $this->minimumPenaltyAmount = $amount;
        return $this;
    }
}
