<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Illuminate\Support\Collection;

/**
 * FI-M002: Early Payment Discount Service
 *
 * Handles calculation and application of early payment discounts (pronto pago).
 * Common terms:
 * - 2/10 Net 30: 2% discount if paid in 10 days, full amount due in 30 days
 * - 1/15 Net 45: 1% discount if paid in 15 days, full amount due in 45 days
 */
class EarlyPaymentDiscountService
{
    /**
     * Common payment terms presets.
     */
    public const TERM_2_10_NET_30 = ['percent' => 2.0, 'days' => 10];
    public const TERM_1_15_NET_45 = ['percent' => 1.0, 'days' => 15];
    public const TERM_3_5_NET_30 = ['percent' => 3.0, 'days' => 5];
    public const TERM_2_15_NET_60 = ['percent' => 2.0, 'days' => 15];

    /**
     * Apply discount terms to an invoice.
     */
    public function applyDiscountTerms(ARInvoice $invoice, float $discountPercent, int $discountDays): ARInvoice
    {
        $invoice->discount_percent = $discountPercent;
        $invoice->discount_days = $discountDays;
        $invoice->discount_date = $invoice->invoice_date->addDays($discountDays);
        $invoice->discount_amount = $this->calculateDiscountAmount($invoice->total_amount, $discountPercent);
        $invoice->save();

        return $invoice;
    }

    /**
     * Apply a preset term (e.g., 2/10 Net 30).
     */
    public function applyPresetTerm(ARInvoice $invoice, string $preset): ARInvoice
    {
        $terms = match ($preset) {
            '2/10 Net 30' => self::TERM_2_10_NET_30,
            '1/15 Net 45' => self::TERM_1_15_NET_45,
            '3/5 Net 30' => self::TERM_3_5_NET_30,
            '2/15 Net 60' => self::TERM_2_15_NET_60,
            default => throw new \InvalidArgumentException("Unknown payment term preset: {$preset}"),
        };

        return $this->applyDiscountTerms($invoice, $terms['percent'], $terms['days']);
    }

    /**
     * Check if payment qualifies for early payment discount.
     */
    public function qualifiesForDiscount(ARInvoice $invoice, Carbon $paymentDate): bool
    {
        if (!$invoice->discount_percent || !$invoice->discount_date) {
            return false;
        }

        if ($invoice->discount_applied) {
            return false; // Already applied
        }

        return $paymentDate->lte($invoice->discount_date);
    }

    /**
     * Calculate discount amount based on invoice total and percentage.
     */
    public function calculateDiscountAmount(float $totalAmount, float $discountPercent): float
    {
        return round($totalAmount * ($discountPercent / 100), 2);
    }

    /**
     * Calculate the discounted amount to pay.
     */
    public function calculateDiscountedTotal(ARInvoice $invoice): float
    {
        if (!$invoice->discount_amount) {
            return $invoice->total_amount;
        }

        return $invoice->total_amount - $invoice->discount_amount;
    }

    /**
     * Get effective amount to pay based on current date.
     */
    public function getEffectiveAmountDue(ARInvoice $invoice, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $remaining = $invoice->total_amount - ($invoice->paid_amount ?? 0);

        if ($this->qualifiesForDiscount($invoice, $asOfDate)) {
            $discountedRemaining = $remaining - $invoice->discount_amount;
            return [
                'original_remaining' => $remaining,
                'discount_available' => true,
                'discount_amount' => $invoice->discount_amount,
                'discounted_remaining' => max(0, $discountedRemaining),
                'discount_deadline' => $invoice->discount_date,
                'days_until_deadline' => $asOfDate->diffInDays($invoice->discount_date, false),
            ];
        }

        return [
            'original_remaining' => $remaining,
            'discount_available' => false,
            'discount_amount' => 0,
            'discounted_remaining' => $remaining,
            'discount_deadline' => $invoice->discount_date,
            'days_until_deadline' => $invoice->discount_date ? $asOfDate->diffInDays($invoice->discount_date, false) : null,
        ];
    }

    /**
     * Apply early payment discount when processing a payment.
     *
     * Returns the discount amount applied.
     */
    public function applyDiscountOnPayment(ARInvoice $invoice, Carbon $paymentDate): float
    {
        if (!$this->qualifiesForDiscount($invoice, $paymentDate)) {
            return 0.0;
        }

        $discountAmount = $invoice->discount_amount;

        $invoice->discount_applied = true;
        $invoice->discount_applied_amount = $discountAmount;
        $invoice->discount_applied_date = $paymentDate;
        $invoice->save();

        return $discountAmount;
    }

    /**
     * Get all invoices eligible for early payment discount within date range.
     */
    public function getEligibleInvoices(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now();
        $endDate = $endDate ?? now()->addDays(30);

        return ARInvoice::where('discount_applied', false)
            ->whereNotNull('discount_date')
            ->where('discount_date', '>=', $startDate)
            ->where('discount_date', '<=', $endDate)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('discount_date')
            ->get();
    }

    /**
     * Get expiring discounts (within next N days).
     */
    public function getExpiringDiscounts(int $withinDays = 5): Collection
    {
        return ARInvoice::where('discount_applied', false)
            ->whereNotNull('discount_date')
            ->where('discount_date', '>=', now())
            ->where('discount_date', '<=', now()->addDays($withinDays))
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('discount_date')
            ->get();
    }

    /**
     * Get summary of potential savings from early payment.
     */
    public function getPotentialSavingsSummary(): array
    {
        $eligibleInvoices = $this->getEligibleInvoices();

        $totalDiscountAvailable = $eligibleInvoices->sum('discount_amount');
        $totalInvoiceAmount = $eligibleInvoices->sum('total_amount');
        $totalRemainingAmount = $eligibleInvoices->sum(fn ($inv) => $inv->total_amount - ($inv->paid_amount ?? 0));

        $byContact = $eligibleInvoices->groupBy('contact_id')->map(function ($invoices) {
            return [
                'invoice_count' => $invoices->count(),
                'total_discount' => $invoices->sum('discount_amount'),
                'total_remaining' => $invoices->sum(fn ($inv) => $inv->total_amount - ($inv->paid_amount ?? 0)),
                'earliest_deadline' => $invoices->min('discount_date'),
            ];
        });

        return [
            'total_eligible_invoices' => $eligibleInvoices->count(),
            'total_invoice_amount' => $totalInvoiceAmount,
            'total_remaining_amount' => $totalRemainingAmount,
            'total_discount_available' => $totalDiscountAvailable,
            'potential_savings_percent' => $totalInvoiceAmount > 0
                ? round(($totalDiscountAvailable / $totalInvoiceAmount) * 100, 2)
                : 0,
            'by_contact' => $byContact,
        ];
    }

    /**
     * Calculate annual savings rate from taking early payment discounts.
     *
     * Formula: APR = (Discount % / (100 - Discount %)) × (365 / (Full Days - Discount Days))
     */
    public function calculateAnnualizedRate(float $discountPercent, int $discountDays, int $fullTermDays): float
    {
        if ($fullTermDays <= $discountDays) {
            return 0;
        }

        $daysGained = $fullTermDays - $discountDays;
        $effectiveRate = ($discountPercent / (100 - $discountPercent)) * (365 / $daysGained) * 100;

        return round($effectiveRate, 2);
    }

    /**
     * Check if taking the discount is financially beneficial.
     *
     * Compares the annualized discount rate against the company's cost of capital.
     */
    public function isDiscountWorthTaking(ARInvoice $invoice, float $costOfCapitalRate = 12.0): array
    {
        if (!$invoice->discount_percent || !$invoice->discount_days) {
            return [
                'worth_taking' => false,
                'reason' => 'No hay descuento disponible para esta factura.',
            ];
        }

        $dueDate = $invoice->due_date;
        $invoiceDate = $invoice->invoice_date;
        $fullTermDays = $invoiceDate->diffInDays($dueDate);

        $annualizedRate = $this->calculateAnnualizedRate(
            $invoice->discount_percent,
            $invoice->discount_days,
            $fullTermDays
        );

        $worthTaking = $annualizedRate > $costOfCapitalRate;

        return [
            'worth_taking' => $worthTaking,
            'annualized_rate' => $annualizedRate,
            'cost_of_capital' => $costOfCapitalRate,
            'savings_vs_cost' => round($annualizedRate - $costOfCapitalRate, 2),
            'discount_amount' => $invoice->discount_amount,
            'reason' => $worthTaking
                ? "El descuento genera un rendimiento anualizado de {$annualizedRate}%, mayor al costo de capital de {$costOfCapitalRate}%."
                : "El descuento genera un rendimiento anualizado de {$annualizedRate}%, menor al costo de capital de {$costOfCapitalRate}%.",
        ];
    }
}
