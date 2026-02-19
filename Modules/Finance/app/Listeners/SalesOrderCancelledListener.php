<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Services\AccountingService;
use Modules\Sales\Events\SalesOrderCancelled;

class SalesOrderCancelledListener
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SalesOrderCancelled $event): void
    {
        $salesOrder = $event->salesOrder;

        Log::info('SalesOrderCancelledListener: Starting', [
            'sales_order_id' => $salesOrder->id,
            'order_number' => $salesOrder->order_number
        ]);

        try {
            DB::beginTransaction();

            // Find associated AR Invoice
            $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();

            if (!$arInvoice) {
                Log::info('SalesOrderCancelledListener: No AR Invoice found to cancel');
                DB::commit();
                return;
            }

            // Handle based on invoice status
            if ($arInvoice->status === 'draft') {
                $arInvoice->update(['status' => 'voided']);

                Log::info('SalesOrderCancelledListener: AR Invoice voided (draft)', [
                    'ar_invoice_id' => $arInvoice->id
                ]);
            } elseif (in_array($arInvoice->status, ['posted', 'partial'])) {
                // Void the invoice
                $arInvoice->update(['status' => 'voided']);

                // Reverse the GL entry if it exists
                if ($arInvoice->journal_entry_id) {
                    $journalEntry = $arInvoice->journalEntry;
                    if ($journalEntry && $journalEntry->status === 'posted') {
                        $this->accountingService->reverseJournalEntry(
                            $journalEntry,
                            "Sales Order #{$salesOrder->order_number} cancelled"
                        );
                    }
                }

                Log::info('SalesOrderCancelledListener: AR Invoice voided + GL reversed', [
                    'ar_invoice_id' => $arInvoice->id,
                    'journal_entry_id' => $arInvoice->journal_entry_id
                ]);
            } else {
                Log::warning('SalesOrderCancelledListener: AR Invoice cannot be voided', [
                    'ar_invoice_id' => $arInvoice->id,
                    'status' => $arInvoice->status
                ]);
            }

            // Update Sales Order status
            $salesOrder->update([
                'invoicing_status' => 'cancelled',
                'financial_status' => 'cancelled'
            ]);

            DB::commit();

            Log::info('SalesOrderCancelledListener: Completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SalesOrderCancelledListener: Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
