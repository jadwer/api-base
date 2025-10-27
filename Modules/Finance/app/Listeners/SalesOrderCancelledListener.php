<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Events\SalesOrderCancelled;

class SalesOrderCancelledListener
{
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

            // Only void if invoice is in draft status
            if ($arInvoice->status === 'draft') {
                $arInvoice->update(['status' => 'voided']);

                Log::info('SalesOrderCancelledListener: AR Invoice voided', [
                    'ar_invoice_id' => $arInvoice->id
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
