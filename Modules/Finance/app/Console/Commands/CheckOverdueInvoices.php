<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Services\CreditManagementService;
use Illuminate\Support\Facades\Log;

/**
 * Check Overdue Invoices Command
 *
 * Automatically updates invoice status to 'overdue' when due_date has passed.
 * This command should be scheduled to run daily.
 *
 * FI-002: Overdue Detection Implementation (P2)
 * FI-M003: Credit Hold Automation (P2)
 *
 * Usage:
 *   php artisan finance:check-overdue
 *   php artisan finance:check-overdue -v
 */
class CheckOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:check-overdue
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue invoices and update their status';

    /**
     * Credit Management Service
     */
    private CreditManagementService $creditManagementService;

    /**
     * Create a new command instance.
     */
    public function __construct(CreditManagementService $creditManagementService)
    {
        parent::__construct();
        $this->creditManagementService = $creditManagementService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking for overdue invoices...');
        $this->newLine();

        // Process AR Invoices
        $arUpdated = $this->processARInvoices();

        // Process AP Invoices
        $apUpdated = $this->processAPInvoices();

        // Process Credit Hold (FI-M003)
        $this->newLine();
        $creditHoldCount = $this->processCreditHold();

        // Summary
        $this->newLine();
        $this->info("✅ AR Invoices updated: {$arUpdated}");
        $this->info("✅ AP Invoices updated: {$apUpdated}");
        $this->info("✅ Customers placed on credit hold: {$creditHoldCount}");
        $this->info("✅ Total invoices updated: " . ($arUpdated + $apUpdated));

        Log::info('Overdue invoices check completed', [
            'ar_updated' => $arUpdated,
            'ap_updated' => $apUpdated,
            'credit_hold' => $creditHoldCount,
            'total' => $arUpdated + $apUpdated,
        ]);

        return 0;
    }

    /**
     * Process AR Invoices (Accounts Receivable)
     */
    private function processARInvoices(): int
    {
        $this->line('Processing AR Invoices (Accounts Receivable)...');

        // Find invoices that are overdue but not marked as such
        $overdueInvoices = ARInvoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'overdue')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now()->toDateString())
            ->where('is_active', true)
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->line('  No AR invoices to update');
            return 0;
        }

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->table(
                ['Invoice #', 'Contact', 'Due Date', 'Amount', 'Days Overdue'],
                $overdueInvoices->map(function ($invoice) {
                    return [
                        $invoice->invoice_number,
                        $invoice->contact->name ?? 'N/A',
                        $invoice->due_date?->format('Y-m-d') ?? 'N/A',
                        '$' . number_format($invoice->total_amount, 2),
                        now()->diffInDays($invoice->due_date) . ' days',
                    ];
                })
            );
            $this->newLine();
        }

        // Update status to overdue
        $updated = 0;
        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            $updated++;

            if ($this->getOutput()->isVerbose()) {
                $this->line("  Updated AR Invoice #{$invoice->invoice_number}");
            }
        }

        $this->line("  Updated {$updated} AR invoice(s)");

        return $updated;
    }

    /**
     * Process AP Invoices (Accounts Payable)
     */
    private function processAPInvoices(): int
    {
        $this->line('Processing AP Invoices (Accounts Payable)...');

        // Find invoices that are overdue but not marked as such
        $overdueInvoices = APInvoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'overdue')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now()->toDateString())
            ->where('is_active', true)
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->line('  No AP invoices to update');
            return 0;
        }

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->table(
                ['Invoice #', 'Contact', 'Due Date', 'Amount', 'Days Overdue'],
                $overdueInvoices->map(function ($invoice) {
                    return [
                        $invoice->invoice_number,
                        $invoice->contact->name ?? 'N/A',
                        $invoice->due_date?->format('Y-m-d') ?? 'N/A',
                        '$' . number_format($invoice->total_amount, 2),
                        now()->diffInDays($invoice->due_date) . ' days',
                    ];
                })
            );
            $this->newLine();
        }

        // Update status to overdue
        $updated = 0;
        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            $updated++;

            if ($this->getOutput()->isVerbose()) {
                $this->line("  Updated AP Invoice #{$invoice->invoice_number}");
            }
        }

        $this->line("  Updated {$updated} AP invoice(s)");

        return $updated;
    }

    /**
     * Process Credit Hold for customers with severely overdue invoices
     *
     * FI-M003: Automated credit hold
     * Places customers on credit hold when they have invoices overdue by 60+ days
     */
    private function processCreditHold(): int
    {
        $this->line('Processing Credit Hold (FI-M003)...');

        $threshold = config('finance.credit_hold_days', 60);

        // Find customers with invoices overdue by threshold days
        $customersToHold = Contact::where('is_customer', true)
            ->where('credit_status', 'active')
            ->whereHas('arInvoices', function ($query) use ($threshold) {
                $query->where('status', 'overdue')
                    ->where('due_date', '<', now()->subDays($threshold)->toDateString());
            })
            ->get();

        if ($customersToHold->isEmpty()) {
            $this->line("  No customers require credit hold");
            return 0;
        }

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->table(
                ['Customer', 'Overdue Amount', 'Days Past Due', 'Action'],
                $customersToHold->map(function ($customer) {
                    $overdueAmount = $this->creditManagementService->getOverdueAmount($customer);
                    $oldestInvoice = $customer->arInvoices()
                        ->where('status', 'overdue')
                        ->orderBy('due_date', 'asc')
                        ->first();

                    return [
                        $customer->name,
                        '$' . number_format($overdueAmount, 2),
                        $oldestInvoice ? now()->diffInDays($oldestInvoice->due_date) . ' days' : 'N/A',
                        'Credit Hold',
                    ];
                })
            );
            $this->newLine();
        }

        // Place customers on credit hold
        $holdCount = 0;
        foreach ($customersToHold as $customer) {
            $overdueAmount = $this->creditManagementService->getOverdueAmount($customer);

            $customer->update([
                'credit_status' => 'hold',
                'credit_hold_at' => now(),
                'credit_hold_reason' => sprintf(
                    "Overdue invoices exceeding %d days. Overdue amount: $%s",
                    $threshold,
                    number_format($overdueAmount, 2)
                ),
            ]);

            $holdCount++;

            if ($this->getOutput()->isVerbose()) {
                $this->warn("  ⚠️  Credit hold placed on: {$customer->name}");
            }

            Log::warning('Credit hold placed on customer', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'overdue_amount' => $overdueAmount,
                'reason' => $customer->credit_hold_reason,
            ]);
        }

        $this->line("  Placed {$holdCount} customer(s) on credit hold");

        return $holdCount;
    }
}
