<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Illuminate\Support\Facades\Log;

/**
 * Check Overdue Invoices Command
 *
 * Automatically updates invoice status to 'overdue' when due_date has passed.
 * This command should be scheduled to run daily.
 *
 * FI-002: Overdue Detection Implementation (P2)
 *
 * Usage:
 *   php artisan finance:check-overdue
 *   php artisan finance:check-overdue --verbose
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

        // Summary
        $this->newLine();
        $this->info("✅ AR Invoices updated: {$arUpdated}");
        $this->info("✅ AP Invoices updated: {$apUpdated}");
        $this->info("✅ Total invoices updated: " . ($arUpdated + $apUpdated));

        Log::info('Overdue invoices check completed', [
            'ar_updated' => $arUpdated,
            'ap_updated' => $apUpdated,
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
}
