<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\APPayment;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\APInvoicePayment;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Models\Account;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class APPaymentService
{
    protected JournalEntryService $journalService;
    protected APInvoiceService $invoiceService;

    public function __construct(
        JournalEntryService $journalService,
        APInvoiceService $invoiceService
    ) {
        $this->journalService = $journalService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Post payment and create GL entry
     * F1 Simple: One payment per invoice
     */
    public function post(APPayment $payment): bool
    {
        if (!$payment->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft payments can be posted.'
            ]);
        }

        $this->validateForPosting($payment);

        return DB::transaction(function () use ($payment) {
            // Get the invoice
            $invoice = $payment->apInvoice;
            
            // Update payment status
            $payment->status = APPayment::STATUS_POSTED;
            $payment->save();

            // Create payment application record (F1: simple, no pivot table)
            $this->applyToInvoice($payment, $invoice);

            // Create GL entry
            $this->createGLEntry($payment);

            // Update invoice payment status
            $this->invoiceService->updatePaymentStatus($invoice);

            return true;
        });
    }

    /**
     * Apply payment to invoice (F1 simple model)
     */
    protected function applyToInvoice(APPayment $payment, APInvoice $invoice): void
    {
        // In F1, we store the applied amount directly in payment
        // F2 will use pivot table for N:M relationships
        
        // Mark payment as applied
        $payment->applied_amount = $payment->amount;
        $payment->save();
    }

    /**
     * Create GL entry for AP Payment
     * Debit: AP Control / Credit: Bank
     */
    protected function createGLEntry(APPayment $payment): void
    {
        $apControlAccountId = config('finance.default_accounts.ap_control');
        $bankAccountId = config('finance.default_accounts.bank');

        $invoice = $payment->apInvoice;

        $this->journalService->createAndPost([
            'date' => $payment->payment_date,
            'description' => "AP Payment #{$payment->payment_number} - Invoice #{$invoice->invoice_number}",
            'reference' => $payment->payment_number,
            'journal_id' => 1,
            'period_id' => 1,
            'source_type' => 'ap_payment',
            'source_id' => $payment->id
        ], [
            [
                'account_id' => $apControlAccountId,
                'debit' => $payment->amount,
                'credit' => 0,
                'description' => 'Payment to supplier'
            ],
            [
                'account_id' => $bankAccountId,
                'debit' => 0,
                'credit' => $payment->amount,
                'description' => 'Bank payment'
            ]
        ]);
    }

    /**
     * Validate payment for posting
     */
    protected function validateForPosting(APPayment $payment): void
    {
        // Validate invoice exists and is posted
        $invoice = $payment->apInvoice;
        
        if (!$invoice) {
            throw ValidationException::withMessages([
                'invoice' => 'Payment must be associated with an invoice.'
            ]);
        }

        if (!$invoice->isPosted()) {
            throw ValidationException::withMessages([
                'invoice' => 'Cannot pay an unposted invoice.'
            ]);
        }

        // Validate amount doesn't exceed invoice balance
        $remainingBalance = $this->invoiceService->getRemainingBalance($invoice);
        
        if ($payment->amount > $remainingBalance) {
            throw ValidationException::withMessages([
                'amount' => "Payment amount ({$payment->amount}) exceeds invoice balance ({$remainingBalance})."
            ]);
        }

        // Validate amount > 0
        if ($payment->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.'
            ]);
        }

        // Validate accounts exist and are postable
        $apControlAccount = Account::find(config('finance.default_accounts.ap_control'));
        $bankAccount = Account::find(config('finance.default_accounts.bank'));

        if (!$apControlAccount || !$apControlAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'AP control account is not configured or not postable.'
            ]);
        }

        if (!$bankAccount || !$bankAccount->isPostable()) {
            throw ValidationException::withMessages([
                'accounts' => 'Bank account is not configured or not postable.'
            ]);
        }
    }
}