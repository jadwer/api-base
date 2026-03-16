<?php

namespace Modules\Finance\Listeners;

use Modules\Purchase\Events\PurchaseOrderReceived;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Services\APInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * PurchaseOrderReceivedListener
 *
 * Automatically creates an AP Invoice when a Purchase Order is received
 */
class PurchaseOrderReceivedListener
{
    public function __construct(
        private APInvoiceService $apInvoiceService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderReceived $event): void
    {
        $purchaseOrder = $event->purchaseOrder;

        // Skip if already invoiced
        if ($purchaseOrder->ap_invoice_id) {
            Log::info("PurchaseOrder already has AP Invoice", [
                'purchase_order_id' => $purchaseOrder->id,
                'ap_invoice_id' => $purchaseOrder->ap_invoice_id,
            ]);
            return;
        }

        try {
            // Create AP Invoice from Purchase Order
            $apInvoice = $this->createAPInvoiceFromPurchaseOrder($purchaseOrder);

            // Update Purchase Order with invoice reference
            $purchaseOrder->update([
                'ap_invoice_id' => $apInvoice->id,
                'invoicing_status' => 'invoiced',
                'financial_status' => 'invoiced',
            ]);

            Log::info("AP Invoice created from PurchaseOrder", [
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_number' => $purchaseOrder->order_number,
                'ap_invoice_id' => $apInvoice->id,
                'ap_invoice_number' => $apInvoice->invoice_number,
                'total_amount' => $apInvoice->total_amount,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create AP Invoice from PurchaseOrder", [
                'purchase_order_id' => $purchaseOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - let the purchase order complete anyway
        }
    }

    /**
     * Create AP Invoice from Purchase Order
     *
     * @param \Modules\Purchase\Models\PurchaseOrder $purchaseOrder
     * @return APInvoice
     */
    private function createAPInvoiceFromPurchaseOrder($purchaseOrder): APInvoice
    {
        // Calculate totals from purchase order items
        // Load purchase order items if not already loaded
        if (!$purchaseOrder->relationLoaded('purchaseOrderItems')) {
            $purchaseOrder->load('purchaseOrderItems');
        }

        $subtotal = $purchaseOrder->purchaseOrderItems->sum(fn($item) => $item->quantity * $item->unit_price);
        $taxAmount = $purchaseOrder->purchaseOrderItems->sum('tax_amount');
        $totalAmount = $subtotal + $taxAmount;

        // Create AP Invoice using service
        return $this->apInvoiceService->createInvoice([
            'invoiceDate' => now()->toDateString(),
            'dueDate' => now()->addDays($purchaseOrder->payment_terms ?? 30)->toDateString(),
            'contactId' => $purchaseOrder->contact_id,
            'purchaseOrderId' => $purchaseOrder->id,
            'currency' => $purchaseOrder->currency ?? 'MXN',
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'notes' => "Auto-generated from Purchase Order #{$purchaseOrder->order_number}",
            'metadata' => [
                'source' => 'purchase_order',
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_number' => $purchaseOrder->order_number,
            ],
        ]);
    }
}
