<?php

namespace Modules\Finance\Listeners;

use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrderCompletedListener
 *
 * Automatically creates an AR Invoice when a Sales Order is completed
 */
class SalesOrderCompletedListener
{
    public function __construct(
        private ARInvoiceService $arInvoiceService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SalesOrderCompleted $event): void
    {
        $salesOrder = $event->salesOrder;

        // Skip if already invoiced
        if ($salesOrder->ar_invoice_id) {
            Log::info("SalesOrder already has AR Invoice", [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $salesOrder->ar_invoice_id,
            ]);
            return;
        }

        try {
            // Create AR Invoice from Sales Order
            $arInvoice = $this->createARInvoiceFromSalesOrder($salesOrder);

            // Update Sales Order with invoice reference
            $salesOrder->update([
                'ar_invoice_id' => $arInvoice->id,
                'invoicing_status' => 'invoiced',
                'financial_status' => 'invoiced',
            ]);

            Log::info("AR Invoice created from SalesOrder", [
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->order_number,
                'ar_invoice_id' => $arInvoice->id,
                'ar_invoice_number' => $arInvoice->invoice_number,
                'total_amount' => $arInvoice->total_amount,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create AR Invoice from SalesOrder", [
                'sales_order_id' => $salesOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - let the sales order complete anyway
            // The invoice can be created manually later
        }
    }

    /**
     * Create AR Invoice from Sales Order
     *
     * @param \Modules\Sales\Models\SalesOrder $salesOrder
     * @return ARInvoice
     */
    private function createARInvoiceFromSalesOrder($salesOrder): ARInvoice
    {
        // Calculate totals from sales order items
        $subtotal = $salesOrder->items->sum(fn($item) => $item->quantity * $item->unit_price);
        $taxAmount = $salesOrder->items->sum('tax_amount');
        $totalAmount = $subtotal + $taxAmount;

        // Create AR Invoice using service
        return $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->toDateString(),
            'dueDate' => now()->addDays($salesOrder->payment_terms ?? 30)->toDateString(),
            'contactId' => $salesOrder->contact_id,
            'currency' => $salesOrder->currency ?? 'MXN',
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'notes' => "Auto-generated from Sales Order #{$salesOrder->order_number}",
            'metadata' => [
                'source' => 'sales_order',
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->order_number,
            ],
        ]);
    }
}
