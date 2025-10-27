<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\APInvoicePosted;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;

/**
 * APInvoicePostedListener
 *
 * Updates related entities when an AP Invoice is posted
 */
class APInvoicePostedListener
{
    /**
     * Handle the event.
     */
    public function handle(APInvoicePosted $event): void
    {
        $invoice = $event->invoice;

        // Update Purchase Order financial status if linked
        if ($invoice->purchase_order_id) {
            $this->updatePurchaseOrderStatus($invoice);
        }

        Log::info("AP Invoice posted", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'purchase_order_id' => $invoice->purchase_order_id,
            'contact_id' => $invoice->contact_id,
            'total_amount' => $invoice->total_amount,
        ]);
    }

    /**
     * Update Purchase Order financial status
     *
     * @param \Modules\Finance\Models\APInvoice $invoice
     * @return void
     */
    private function updatePurchaseOrderStatus($invoice): void
    {
        try {
            $purchaseOrder = PurchaseOrder::find($invoice->purchase_order_id);

            if ($purchaseOrder) {
                $purchaseOrder->update([
                    'financial_status' => 'invoiced',
                ]);

                Log::info("Purchase Order financial status updated", [
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_order_number' => $purchaseOrder->order_number,
                    'financial_status' => 'invoiced',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update Purchase Order status", [
                'invoice_id' => $invoice->id,
                'purchase_order_id' => $invoice->purchase_order_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
