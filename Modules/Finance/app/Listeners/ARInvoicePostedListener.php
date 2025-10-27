<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\ARInvoicePosted;
use Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\Log;

/**
 * ARInvoicePostedListener
 *
 * Updates related entities when an AR Invoice is posted
 */
class ARInvoicePostedListener
{
    /**
     * Handle the event.
     */
    public function handle(ARInvoicePosted $event): void
    {
        $invoice = $event->invoice;

        // Update Sales Order financial status if linked
        if ($invoice->sales_order_id) {
            $this->updateSalesOrderStatus($invoice);
        }

        Log::info("AR Invoice posted", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'sales_order_id' => $invoice->sales_order_id,
            'contact_id' => $invoice->contact_id,
            'total_amount' => $invoice->total_amount,
        ]);
    }

    /**
     * Update Sales Order financial status
     *
     * @param \Modules\Finance\Models\ARInvoice $invoice
     * @return void
     */
    private function updateSalesOrderStatus($invoice): void
    {
        try {
            $salesOrder = SalesOrder::find($invoice->sales_order_id);

            if ($salesOrder) {
                $salesOrder->update([
                    'financial_status' => 'invoiced',
                ]);

                Log::info("Sales Order financial status updated", [
                    'sales_order_id' => $salesOrder->id,
                    'sales_order_number' => $salesOrder->order_number,
                    'financial_status' => 'invoiced',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update Sales Order status", [
                'invoice_id' => $invoice->id,
                'sales_order_id' => $invoice->sales_order_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
