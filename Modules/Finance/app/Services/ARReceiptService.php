<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ARReceipt;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\ARInvoiceReceipt;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Models\Account;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ARReceiptService
{
    protected JournalEntryService $journalService;
    protected ARInvoiceService $invoiceService;

    public function __construct(
        JournalEntryService $journalService,
        ARInvoiceService $invoiceService
    ) {
        $this->journalService = $journalService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Post receipt and create GL entry
     * F1 Simple: One receipt per invoice
     */
    public function post(ARReceipt $receipt): bool
    {
        if (!$receipt->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft receipts can be posted.'
            ]);
        }

        $this->validateForPosting($receipt);

        return DB::transaction(function () use ($receipt) {
            // Get the invoice
            $invoice = $receipt->arInvoice;
            
            // Update receipt status
            $receipt->status = ARReceipt::STATUS_POSTED;
            $receipt->save();

            // Create receipt application record (F1: simple, no pivot table)
            $this->applyToInvoice($receipt, $invoice);

            // Create GL entry
            $this->createGLEntry($receipt);

            // Update invoice payment status
            $this->invoiceService->updatePaymentStatus($invoice);

            return true;
        });
    }

    /**
     * Apply receipt to invoice (F1 simple model)
     */
    protected function applyToInvoice(ARReceipt $receipt, ARInvoice $invoice): void
    {
        // In F1, we store the applied amount directly in receipt
        // F2 will use pivot table for N:M relationships
        
        // Mark receipt as applied
        $receipt->applied_amount = $receipt->amount;
        $receipt->save();
    }

    /**
     * Create GL entry for AR Receipt
     * Debit: Bank / Credit: AR Control
     */
    protected function createGLEntry(ARReceipt $receipt): void
    {
        $bankAccountId = config('finance.default_accounts.bank');
        $arControlAccountId = config('finance.default_accounts.ar_control');

        $invoice = $receipt->arInvoice;

        $this->journalService->createAndPost([
            'date' => $receipt->receipt_date,
            'description' => "AR Receipt #{$receipt->receipt_number} - Invoice #{$invoice->invoice_number}",
            'reference' => $receipt->receipt_number,
            'journal_id' => 1,
            'period_id' => 1,
            'source_type' => 'ar_receipt',
            'source_id' => $receipt->id
        ], [
            [
                'account_id' => $bankAccountId,
                'debit' => $receipt->amount,
                'credit' => 0,
                'description' => 'Bank deposit'
            ],
            [
                'account_id' => $arControlAccountId,
                'debit' => 0,
                'credit' => $receipt->amount,
                'description' => 'Receipt from customer'
            ]
        ]);
    }

    /**
     * Validate receipt for posting
     */
    protected function validateForPosting(ARReceipt $receipt): void
    {
        // Validate invoice exists and is posted
        $invoice = $receipt->arInvoice;
        
        if (!$invoice) {
            throw ValidationException::withMessages([
                'invoice' => 'Receipt must be associated with an invoice.'
            ]);
        }

        if (!$invoice->isPosted()) {
            throw ValidationException::withMessages([
                'invoice' => 'Cannot receive payment for an unposted invoice.'
            ]);
        }

        // Validate amount doesn't exceed invoice balance
        $remainingBalance = $this->invoiceService->getRemainingBalance($invoice);
        
        if ($receipt->amount > $remainingBalance) {
            throw ValidationException::withMessages([
                'amount' => "Receipt amount ({$receipt->amount}) exceeds invoice balance ({$remainingBalance})."
            ]);
        }

        // Validate amount > 0
        if ($receipt->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Receipt amount must be greater than zero.'
            ]);
        }

        // Validate accounts exist and are postable
        $bankAccount = Account::find(config('finance.default_accounts.bank'));
        $arControlAccount = Account::find(config('finance.default_accounts.ar_control'));

        if (!$bankAccount || !$bankAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Bank account is not configured or not postable.'
            ]);
        }

        if (!$arControlAccount || !$arControlAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'AR control account is not configured or not postable.'
            ]);
        }
    }
}