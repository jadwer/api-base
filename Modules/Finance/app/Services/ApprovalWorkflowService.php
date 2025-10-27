<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Contacts\Models\Contact;

class ApprovalWorkflowService
{
    /**
     * Check if AR Invoice requires approval
     *
     * @param ARInvoice $invoice
     * @return bool
     */
    public function requiresARApproval(ARInvoice $invoice): bool
    {
        $rules = [
            // Rule 1: Amount threshold (>50,000)
            $invoice->total_amount > config('finance.ar_approval_threshold', 50000),

            // Rule 2: Customer has high risk level
            $this->isHighRiskCustomer($invoice->contact_id),

            // Rule 3: First-time customer (no payment history)
            $this->isFirstTimeCustomer($invoice->contact_id),

            // Rule 4: Foreign currency
            $invoice->currency !== config('app.base_currency', 'MXN'),
        ];

        return collect($rules)->contains(true);
    }

    /**
     * Check if AP Invoice requires approval
     *
     * @param APInvoice $invoice
     * @return bool
     */
    public function requiresAPApproval(APInvoice $invoice): bool
    {
        $rules = [
            // Rule 1: Amount threshold (>100,000)
            $invoice->total_amount > config('finance.ap_approval_threshold', 100000),

            // Rule 2: New supplier (no payment history)
            $this->isFirstTimeSupplier($invoice->contact_id),

            // Rule 3: Foreign currency
            $invoice->currency !== config('app.base_currency', 'MXN'),

            // Rule 4: Duplicate invoice check
            $this->isPotentialDuplicate($invoice),
        ];

        return collect($rules)->contains(true);
    }

    /**
     * Get required approvers for AR Invoice
     *
     * @param ARInvoice $invoice
     * @return Collection
     */
    public function getRequiredARApprovers(ARInvoice $invoice): Collection
    {
        $approvers = collect();

        // Tier 1: Finance Manager (>50,000)
        if ($invoice->total_amount > 50000) {
            $approvers->push([
                'role' => 'finance_manager',
                'permission' => 'finance.approve-ar-tier1',
                'tier' => 1,
                'reason' => 'Amount exceeds 50,000',
            ]);
        }

        // Tier 2: Finance Director (>100,000)
        if ($invoice->total_amount > 100000) {
            $approvers->push([
                'role' => 'finance_director',
                'permission' => 'finance.approve-ar-tier2',
                'tier' => 2,
                'reason' => 'Amount exceeds 100,000',
            ]);
        }

        // Tier 3: CFO (>500,000)
        if ($invoice->total_amount > 500000) {
            $approvers->push([
                'role' => 'cfo',
                'permission' => 'finance.approve-ar-tier3',
                'tier' => 3,
                'reason' => 'Amount exceeds 500,000',
            ]);
        }

        // Credit Manager: High risk or first-time customers
        if ($this->isHighRiskCustomer($invoice->contact_id) || $this->isFirstTimeCustomer($invoice->contact_id)) {
            $approvers->push([
                'role' => 'credit_manager',
                'permission' => 'finance.approve-credit',
                'tier' => 1,
                'reason' => 'High risk or first-time customer',
            ]);
        }

        // Treasury: Foreign currency
        if ($invoice->currency !== config('app.base_currency', 'MXN')) {
            $approvers->push([
                'role' => 'treasury',
                'permission' => 'finance.approve-forex',
                'tier' => 1,
                'reason' => 'Foreign currency transaction',
            ]);
        }

        return $approvers;
    }

    /**
     * Get required approvers for AP Invoice
     *
     * @param APInvoice $invoice
     * @return Collection
     */
    public function getRequiredAPApprovers(APInvoice $invoice): Collection
    {
        $approvers = collect();

        // Tier 1: Accounts Payable Manager (>100,000)
        if ($invoice->total_amount > 100000) {
            $approvers->push([
                'role' => 'ap_manager',
                'permission' => 'finance.approve-ap-tier1',
                'tier' => 1,
                'reason' => 'Amount exceeds 100,000',
            ]);
        }

        // Tier 2: Finance Director (>250,000)
        if ($invoice->total_amount > 250000) {
            $approvers->push([
                'role' => 'finance_director',
                'permission' => 'finance.approve-ap-tier2',
                'tier' => 2,
                'reason' => 'Amount exceeds 250,000',
            ]);
        }

        // Tier 3: CFO (>1,000,000)
        if ($invoice->total_amount > 1000000) {
            $approvers->push([
                'role' => 'cfo',
                'permission' => 'finance.approve-ap-tier3',
                'tier' => 3,
                'reason' => 'Amount exceeds 1,000,000',
            ]);
        }

        // Procurement: First-time supplier
        if ($this->isFirstTimeSupplier($invoice->contact_id)) {
            $approvers->push([
                'role' => 'procurement',
                'permission' => 'finance.approve-new-supplier',
                'tier' => 1,
                'reason' => 'First-time supplier',
            ]);
        }

        // Treasury: Foreign currency
        if ($invoice->currency !== config('app.base_currency', 'MXN')) {
            $approvers->push([
                'role' => 'treasury',
                'permission' => 'finance.approve-forex',
                'tier' => 1,
                'reason' => 'Foreign currency transaction',
            ]);
        }

        return $approvers;
    }

    /**
     * Approve AR Invoice
     *
     * @param ARInvoice $invoice
     * @param int $userId
     * @param string $notes
     * @return bool
     * @throws \Exception
     */
    public function approveARInvoice(ARInvoice $invoice, int $userId, string $notes = ''): bool
    {
        return DB::transaction(function () use ($invoice, $userId, $notes) {
            // Get current approvals
            $approvals = $invoice->metadata['approvals'] ?? [];

            // Add new approval
            $approvals[] = [
                'user_id' => $userId,
                'approved_at' => now()->toDateTimeString(),
                'notes' => $notes,
            ];

            // Update metadata
            $metadata = $invoice->metadata;
            $metadata['approvals'] = $approvals;

            // Check if all required approvers have approved
            $requiredApprovers = $this->getRequiredARApprovers($invoice);
            $allApproved = count($approvals) >= $requiredApprovers->count();

            if ($allApproved) {
                $invoice->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by_id' => $userId,
                    'metadata' => $metadata,
                ]);
            } else {
                $invoice->update([
                    'approval_status' => 'pending',
                    'metadata' => $metadata,
                ]);
            }

            return $allApproved;
        });
    }

    /**
     * Reject AR Invoice
     *
     * @param ARInvoice $invoice
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function rejectARInvoice(ARInvoice $invoice, int $userId, string $reason): bool
    {
        $metadata = $invoice->metadata;
        $metadata['rejection'] = [
            'user_id' => $userId,
            'rejected_at' => now()->toDateTimeString(),
            'reason' => $reason,
        ];

        $invoice->update([
            'approval_status' => 'rejected',
            'metadata' => $metadata,
        ]);

        return true;
    }

    /**
     * Check if customer is high risk
     *
     * @param int $contactId
     * @return bool
     */
    private function isHighRiskCustomer(int $contactId): bool
    {
        $contact = Contact::find($contactId);

        if (!$contact || !$contact->is_customer) {
            return false;
        }

        // Check metadata for risk level
        $riskLevel = $contact->metadata['risk_level'] ?? 'low';

        return $riskLevel === 'high';
    }

    /**
     * Check if this is customer's first invoice
     *
     * @param int $contactId
     * @return bool
     */
    private function isFirstTimeCustomer(int $contactId): bool
    {
        $invoiceCount = ARInvoice::where('contact_id', $contactId)
            ->where('status', 'paid')
            ->count();

        return $invoiceCount === 0;
    }

    /**
     * Check if this is supplier's first invoice
     *
     * @param int $contactId
     * @return bool
     */
    private function isFirstTimeSupplier(int $contactId): bool
    {
        $invoiceCount = APInvoice::where('contact_id', $contactId)
            ->where('status', 'paid')
            ->count();

        return $invoiceCount === 0;
    }

    /**
     * Check if AP Invoice is potential duplicate
     *
     * @param APInvoice $invoice
     * @return bool
     */
    private function isPotentialDuplicate(APInvoice $invoice): bool
    {
        // Check for same supplier, same amount, within 30 days
        $duplicateCount = APInvoice::where('contact_id', $invoice->contact_id)
            ->where('total_amount', $invoice->total_amount)
            ->where('invoice_date', '>=', now()->subDays(30)->toDateString())
            ->where('id', '!=', $invoice->id)
            ->count();

        return $duplicateCount > 0;
    }

    /**
     * Get approval history for invoice
     *
     * @param ARInvoice|APInvoice $invoice
     * @return Collection
     */
    public function getApprovalHistory($invoice): Collection
    {
        $approvals = $invoice->metadata['approvals'] ?? [];

        return collect($approvals)->map(function ($approval) {
            return [
                'user_id' => $approval['user_id'],
                'approved_at' => $approval['approved_at'],
                'notes' => $approval['notes'] ?? '',
            ];
        });
    }

    /**
     * Get pending approvals count
     *
     * @param string $type 'ar' or 'ap'
     * @return int
     */
    public function getPendingApprovalsCount(string $type = 'ar'): int
    {
        $model = $type === 'ar' ? ARInvoice::class : APInvoice::class;

        return $model::where('approval_status', 'pending')
            ->where('is_active', true)
            ->count();
    }
}
