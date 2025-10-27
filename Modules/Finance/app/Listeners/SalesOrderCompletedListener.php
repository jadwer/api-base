<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Sales\Models\SalesOrder;

class SalesOrderCompletedListener
{
    protected ARInvoiceService $arInvoiceService;

    public function __construct(ARInvoiceService $arInvoiceService)
    {
        $this->arInvoiceService = $arInvoiceService;
    }

    /**
     * Handle the event.
     */
    public function handle(SalesOrderCompleted $event): void
    {
        $salesOrder = $event->salesOrder;

        Log::info('SalesOrderCompletedListener: Starting', [
            'sales_order_id' => $salesOrder->id,
            'order_number' => $salesOrder->order_number,
            'total_amount' => $salesOrder->total_amount
        ]);

        try {
            DB::beginTransaction();

            // Check if AR Invoice already exists (idempotency)
            $existingInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();

            if ($existingInvoice) {
                Log::warning('SalesOrderCompletedListener: AR Invoice already exists', [
                    'ar_invoice_id' => $existingInvoice->id
                ]);
                DB::rollBack();
                return;
            }

            // Create AR Invoice from Sales Order
            $arInvoice = $this->createARInvoiceFromSalesOrder($salesOrder);

            Log::info('SalesOrderCompletedListener: AR Invoice created', [
                'ar_invoice_id' => $arInvoice->id,
                'invoice_number' => $arInvoice->invoice_number
            ]);

            // Sync Sales Order
            $this->syncSalesOrder($salesOrder, $arInvoice);

            DB::commit();

            Log::info('SalesOrderCompletedListener: Completed successfully', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $arInvoice->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SalesOrderCompletedListener: Failed', [
                'sales_order_id' => $salesOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't update status on error - let it remain in current state
            // The error is logged and will be retried or handled manually

            throw $e;
        }
    }

    /**
     * Create AR Invoice from Sales Order
     */
    protected function createARInvoiceFromSalesOrder(SalesOrder $salesOrder): ARInvoice
    {
        // Load sales order items
        $salesOrder->load('items.product');

        // Generate invoice number (using service or sequence)
        $invoiceNumber = $this->generateInvoiceNumber();

        // Create AR Invoice
        $arInvoice = ARInvoice::create([
            'invoice_number' => $invoiceNumber,
            'contact_id' => $salesOrder->contact_id,
            'sales_order_id' => $salesOrder->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30), // Default 30 days
            'subtotal' => $salesOrder->subtotal ?? $salesOrder->total_amount,
            'tax_amount' => $salesOrder->tax_amount ?? 0,
            'total_amount' => $salesOrder->total_amount,
            'paid_amount' => 0,
            'remaining_balance' => $salesOrder->total_amount,
            'currency' => $salesOrder->currency ?? 'MXN',
            'status' => 'draft',
            'notes' => "Auto-generated from Sales Order {$salesOrder->order_number}"
        ]);

        // Create AR Invoice Lines from Sales Order Items
        foreach ($salesOrder->items as $item) {
            $arInvoice->aRInvoiceLines()->create([
                'product_id' => $item->product_id,
                'description' => $item->product->name ?? 'Product',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'tax_amount' => 0, // Calculate if needed
                'total_amount' => $item->total
            ]);
        }

        return $arInvoice->fresh();
    }

    /**
     * Sync Sales Order with AR Invoice
     */
    protected function syncSalesOrder(SalesOrder $salesOrder, ARInvoice $arInvoice): void
    {
        $salesOrder->update([
            'ar_invoice_id' => $arInvoice->id,
            'invoicing_status' => 'complete',
            'financial_status' => 'not_invoiced' // Will change to 'invoiced' when AR posted
        ]);
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        // Simple implementation - can be enhanced with SequenceService
        $year = now()->year;
        $lastInvoice = ARInvoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('AR-%d-%04d', $year, $nextNumber);
    }
}
