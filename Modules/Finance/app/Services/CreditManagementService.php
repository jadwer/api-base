<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

class CreditManagementService
{
    /**
     * Validate if customer can take on new credit amount
     *
     * @param Contact $contact
     * @param float $newAmount
     * @return bool
     * @throws \Exception
     */
    public function validateCustomerCredit(Contact $contact, float $newAmount): bool
    {
        // Only validate if contact is a customer
        if (!$contact->is_customer) {
            throw new \Exception("Contact #{$contact->id} is not a customer");
        }

        $currentBalance = $this->getCurrentARBalance($contact);
        $totalExposure = $currentBalance + $newAmount;

        // Rule 1: Credit limit validation
        if ($contact->credit_limit && $totalExposure > $contact->credit_limit) {
            throw new \Exception(
                "Credit limit exceeded. Current balance: {$currentBalance}, " .
                "New amount: {$newAmount}, Credit limit: {$contact->credit_limit}"
            );
        }

        // Rule 2: Overdue validation - block if has overdue invoices
        $overdueAmount = $this->getOverdueAmount($contact);
        if ($overdueAmount > 0) {
            throw new \Exception(
                "Customer has overdue invoices totaling: {$overdueAmount}. " .
                "Payment required before new credit."
            );
        }

        // Rule 3: Payment history validation
        $paymentScore = $this->calculatePaymentScore($contact);
        $minimumScore = 60; // 60% on-time payment rate required

        if ($paymentScore < $minimumScore) {
            throw new \Exception(
                "Poor payment history. Payment score: {$paymentScore}%, " .
                "Required: {$minimumScore}%"
            );
        }

        return true;
    }

    /**
     * Get current AR balance for customer
     *
     * @param Contact $contact
     * @return float
     */
    public function getCurrentARBalance(Contact $contact): float
    {
        return ARInvoice::where('contact_id', $contact->id)
            ->where('status', '!=', 'paid')
            ->where('is_active', true)
            ->sum(DB::raw('total_amount - paid_amount'));
    }

    /**
     * Get total overdue amount for customer
     *
     * @param Contact $contact
     * @return float
     */
    public function getOverdueAmount(Contact $contact): float
    {
        return ARInvoice::where('contact_id', $contact->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->where('is_active', true)
            ->sum(DB::raw('total_amount - paid_amount'));
    }

    /**
     * Calculate payment score (percentage of on-time payments)
     *
     * NOTE: Currently returns 100% for all customers. Full implementation requires
     * adding 'paid_date' field to ar_invoices table to track actual payment dates.
     *
     * @param Contact $contact
     * @return float
     */
    public function calculatePaymentScore(Contact $contact): float
    {
        // TODO: Add 'paid_date' field to ar_invoices migration for accurate payment scoring
        // For now, return 100% (good standing) for all customers
        return 100.0;

        /* Full implementation (requires paid_date field):
        $totalInvoices = ARInvoice::where('contact_id', $contact->id)
            ->where('status', 'paid')
            ->where('is_active', true)
            ->count();

        if ($totalInvoices === 0) {
            return 100.0; // New customer - give benefit of doubt
        }

        // Count invoices paid on or before due date
        $onTimePayments = ARInvoice::where('contact_id', $contact->id)
            ->where('status', 'paid')
            ->where('is_active', true)
            ->whereRaw('paid_date <= due_date')
            ->count();

        return round(($onTimePayments / $totalInvoices) * 100, 2);
        */
    }

    /**
     * Get credit analysis report for customer
     *
     * @param Contact $contact
     * @return array
     */
    public function getCreditAnalysis(Contact $contact): array
    {
        $currentBalance = $this->getCurrentARBalance($contact);
        $overdueAmount = $this->getOverdueAmount($contact);
        $paymentScore = $this->calculatePaymentScore($contact);

        $creditLimit = $contact->credit_limit ?? 0;
        $availableCredit = max(0, $creditLimit - $currentBalance);
        $creditUtilization = $creditLimit > 0
            ? round(($currentBalance / $creditLimit) * 100, 2)
            : 0;

        return [
            'contact_id' => $contact->id,
            'contact_name' => $contact->name,
            'credit_limit' => $creditLimit,
            'current_balance' => $currentBalance,
            'available_credit' => $availableCredit,
            'credit_utilization_percent' => $creditUtilization,
            'overdue_amount' => $overdueAmount,
            'payment_score' => $paymentScore,
            'credit_status' => $this->getCreditStatus($contact),
            'risk_level' => $this->calculateRiskLevel($contact),
        ];
    }

    /**
     * Get aging summary for customer
     *
     * @param Contact $contact
     * @return Collection
     */
    public function getAgingSummary(Contact $contact): Collection
    {
        $invoices = ARInvoice::where('contact_id', $contact->id)
            ->where('status', '!=', 'paid')
            ->where('is_active', true)
            ->get();

        $buckets = [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];

        foreach ($invoices as $invoice) {
            $balance = $invoice->total_amount - $invoice->paid_amount;
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now(), false);

            $bucket = match (true) {
                $daysOverdue < 0 => 'current',
                $daysOverdue <= 30 => '1-30',
                $daysOverdue <= 60 => '31-60',
                $daysOverdue <= 90 => '61-90',
                default => '90+'
            };

            $buckets[$bucket] += $balance;
        }

        return collect($buckets);
    }

    /**
     * Get credit status (good, warning, blocked)
     *
     * @param Contact $contact
     * @return string
     */
    private function getCreditStatus(Contact $contact): string
    {
        $currentBalance = $this->getCurrentARBalance($contact);
        $overdueAmount = $this->getOverdueAmount($contact);
        $creditLimit = $contact->credit_limit ?? 0;

        // Blocked if has overdue amounts
        if ($overdueAmount > 0) {
            return 'blocked';
        }

        // Warning if utilization > 80%
        if ($creditLimit > 0 && ($currentBalance / $creditLimit) > 0.8) {
            return 'warning';
        }

        return 'good';
    }

    /**
     * Calculate risk level based on payment history and credit utilization
     *
     * @param Contact $contact
     * @return string
     */
    private function calculateRiskLevel(Contact $contact): string
    {
        $paymentScore = $this->calculatePaymentScore($contact);
        $currentBalance = $this->getCurrentARBalance($contact);
        $creditLimit = $contact->credit_limit ?? 0;

        $utilization = $creditLimit > 0 ? ($currentBalance / $creditLimit) : 0;

        // High risk: Poor payment score or high utilization
        if ($paymentScore < 60 || $utilization > 0.9) {
            return 'high';
        }

        // Medium risk: Average payment score or moderate utilization
        if ($paymentScore < 80 || $utilization > 0.7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Update customer credit status after invoice/payment activity
     *
     * @param int $contactId
     * @return void
     */
    public function updateCreditStatus(int $contactId): void
    {
        $contact = Contact::find($contactId);

        if (!$contact || !$contact->is_customer) {
            return;
        }

        $status = $this->getCreditStatus($contact);
        $riskLevel = $this->calculateRiskLevel($contact);

        // Update contact metadata
        $metadata = $contact->metadata ?? [];
        $metadata['credit_status'] = $status;
        $metadata['risk_level'] = $riskLevel;
        $metadata['last_credit_check'] = now()->toDateTimeString();

        $contact->update(['metadata' => $metadata]);
    }
}
