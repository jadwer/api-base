<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\APPayment;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Models\Account;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class APInvoiceService
{
    protected JournalEntryService $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Post AP Invoice and create GL entry
     */
    public function post(APInvoice $invoice): bool
    {
        if (!$invoice->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft invoices can be posted.'
            ]);
        }

        $this->validateForPosting($invoice);

        return DB::transaction(function () use ($invoice) {
            // Update invoice status
            $invoice->status = APInvoice::STATUS_POSTED;
            $invoice->save();

            // Create automatic GL entry
            $this->createGLEntry($invoice);

            return true;
        });
    }

    /**
     * Create GL entry for AP Invoice
     * Debit: Expense Account / Credit: AP Control Account
     */
    protected function createGLEntry(APInvoice $invoice): void
    {
        $expenseAccountId = config('finance.default_accounts.expense');
        $apControlAccountId = config('finance.default_accounts.ap_control');

        $this->journalService->createAndPost([
            'date' => $invoice->invoice_date,
            'description' => "AP Invoice #{$invoice->invoice_number} - {$invoice->contact->name}",
            'reference' => $invoice->invoice_number,
            'journal_id' => 1, // Default journal
            'period_id' => 1,  // Default period
            'source_type' => 'ap_invoice',
            'source_id' => $invoice->id
        ], [
            [
                'account_id' => $expenseAccountId,
                'debit' => $invoice->total,
                'credit' => 0,
                'description' => 'Purchase expense'
            ],
            [
                'account_id' => $apControlAccountId,
                'debit' => 0,
                'credit' => $invoice->total,
                'description' => 'Accounts Payable'
            ]
        ]);
    }

    /**
     * Validate invoice for posting
     */
    protected function validateForPosting(APInvoice $invoice): void
    {
        // Validate contact exists and is supplier
        if (!$invoice->contact) {
            throw ValidationException::withMessages([
                'contact' => 'Invoice must have a valid contact.'
            ]);
        }

        if (!$invoice->contact->isSupplier()) {
            throw ValidationException::withMessages([
                'contact' => 'Contact must be a supplier for AP invoices.'
            ]);
        }

        // Validate total > 0
        if ($invoice->total <= 0) {
            throw ValidationException::withMessages([
                'total' => 'Invoice total must be greater than zero.'
            ]);
        }

        // Validate accounts exist and are postable
        $expenseAccount = Account::find(config('finance.default_accounts.expense'));
        $apControlAccount = Account::find(config('finance.default_accounts.ap_control'));

        if (!$expenseAccount || !$expenseAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Default expense account is not configured or not postable.'
            ]);
        }

        if (!$apControlAccount || !$apControlAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Default AP control account is not configured or not postable.'
            ]);
        }
    }

    /**
     * Calculate remaining balance for payment application
     * F1 Simple: Direct relationship with APPayment
     */
    public function getRemainingBalance(APInvoice $invoice): float
    {
        if (!$invoice->isPosted()) {
            return 0.00;
        }

        // F1: Simple model - payments directly reference invoice
        $totalPaid = APPayment::where('ap_invoice_id', $invoice->id)
            ->where('status', APPayment::STATUS_POSTED)
            ->sum('amount');
            
        return max(0, $invoice->total - $totalPaid);
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(APInvoice $invoice): bool
    {
        return $this->getRemainingBalance($invoice) < 0.01;
    }

    /**
     * Update payment status based on payments
     */
    public function updatePaymentStatus(APInvoice $invoice): void
    {
        if (!$invoice->isPosted()) {
            return;
        }

        $remaining = $this->getRemainingBalance($invoice);

        if ($remaining < 0.01) {
            $invoice->status = APInvoice::STATUS_PAID;
        } elseif ($remaining < $invoice->total) {
            $invoice->status = APInvoice::STATUS_PARTIALLY_PAID;
        } else {
            $invoice->status = APInvoice::STATUS_POSTED;
        }

        $invoice->save();
    }
}