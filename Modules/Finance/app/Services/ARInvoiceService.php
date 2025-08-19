<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\ARReceipt;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Models\Account;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ARInvoiceService
{
    protected JournalEntryService $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Post AR Invoice and create GL entry
     */
    public function post(ARInvoice $invoice): bool
    {
        if (!$invoice->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft invoices can be posted.'
            ]);
        }

        $this->validateForPosting($invoice);

        return DB::transaction(function () use ($invoice) {
            // Update invoice status
            $invoice->status = ARInvoice::STATUS_POSTED;
            $invoice->save();

            // Create automatic GL entry
            $this->createGLEntry($invoice);

            return true;
        });
    }

    /**
     * Create GL entry for AR Invoice
     * Debit: AR Control Account / Credit: Revenue Account
     */
    protected function createGLEntry(ARInvoice $invoice): void
    {
        $arControlAccountId = config('finance.default_accounts.ar_control');
        $revenueAccountId = config('finance.default_accounts.revenue');

        $this->journalService->createAndPost([
            'date' => $invoice->invoice_date,
            'description' => "AR Invoice #{$invoice->invoice_number} - {$invoice->contact->name}",
            'reference' => $invoice->invoice_number,
            'journal_id' => 1, // Default journal
            'period_id' => 1,  // Default period
            'source_type' => 'ar_invoice',
            'source_id' => $invoice->id
        ], [
            [
                'account_id' => $arControlAccountId,
                'debit' => $invoice->total,
                'credit' => 0,
                'description' => 'Accounts Receivable'
            ],
            [
                'account_id' => $revenueAccountId,
                'debit' => 0,
                'credit' => $invoice->total,
                'description' => 'Sales Revenue'
            ]
        ]);
    }

    /**
     * Validate invoice for posting
     */
    protected function validateForPosting(ARInvoice $invoice): void
    {
        // Validate contact exists and is customer
        if (!$invoice->contact) {
            throw ValidationException::withMessages([
                'contact' => 'Invoice must have a valid contact.'
            ]);
        }

        if (!$invoice->contact->isCustomer()) {
            throw ValidationException::withMessages([
                'contact' => 'Contact must be a customer for AR invoices.'
            ]);
        }

        // Validate total > 0
        if ($invoice->total <= 0) {
            throw ValidationException::withMessages([
                'total' => 'Invoice total must be greater than zero.'
            ]);
        }

        // Check credit limit (opcional F1, pero lo agregamos simple)
        if (!$invoice->contact->hasAvailableCredit($invoice->total)) {
            throw ValidationException::withMessages([
                'credit' => 'Customer has exceeded credit limit.'
            ]);
        }

        // Validate accounts exist and are postable
        $arControlAccount = Account::find(config('finance.default_accounts.ar_control'));
        $revenueAccount = Account::find(config('finance.default_accounts.revenue'));

        if (!$arControlAccount || !$arControlAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Default AR control account is not configured or not postable.'
            ]);
        }

        if (!$revenueAccount || !$revenueAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Default revenue account is not configured or not postable.'
            ]);
        }
    }

    /**
     * Calculate remaining balance for receipt application
     * F1 Simple: Direct relationship with ARReceipt
     */
    public function getRemainingBalance(ARInvoice $invoice): float
    {
        if (!$invoice->isPosted()) {
            return 0.00;
        }

        // F1: Simple model - receipts directly reference invoice
        $totalReceived = ARReceipt::where('ar_invoice_id', $invoice->id)
            ->where('status', ARReceipt::STATUS_POSTED)
            ->sum('amount');
            
        return max(0, $invoice->total - $totalReceived);
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(ARInvoice $invoice): bool
    {
        return $this->getRemainingBalance($invoice) < 0.01;
    }

    /**
     * Update payment status based on receipts
     */
    public function updatePaymentStatus(ARInvoice $invoice): void
    {
        if (!$invoice->isPosted()) {
            return;
        }

        $remaining = $this->getRemainingBalance($invoice);

        if ($remaining < 0.01) {
            $invoice->status = ARInvoice::STATUS_PAID;
        } elseif ($remaining < $invoice->total) {
            $invoice->status = ARInvoice::STATUS_PARTIALLY_PAID;
        } else {
            $invoice->status = ARInvoice::STATUS_POSTED;
        }

        $invoice->save();
    }
}