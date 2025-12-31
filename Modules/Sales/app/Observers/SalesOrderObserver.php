<?php

namespace Modules\Sales\Observers;

use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrderObserver
 *
 * SA-M001: Automatically creates AR Invoice when Sales Order is delivered
 */
class SalesOrderObserver
{
    public function __construct(
        private ARInvoiceService $arInvoiceService
    ) {}

    /**
     * Handle the SalesOrder "updated" event.
     *
     * SA-M001: Automatically generate AR Invoice when status changes to 'delivered'
     */
    public function updated(SalesOrder $salesOrder): void
    {
        // Check if status was changed to 'delivered'
        if ($salesOrder->isDirty('status') && $salesOrder->status === 'delivered') {
            try {
                // Check if auto-invoicing is enabled
                if (!config('sales.auto_invoice_on_delivery', true)) {
                    Log::info('Auto-invoicing disabled, skipping AR Invoice creation', [
                        'sales_order_id' => $salesOrder->id,
                        'sales_order_number' => $salesOrder->order_number,
                    ]);
                    return;
                }

                // Only auto-generate if there's no invoice yet
                if (!$this->arInvoiceService->invoiceExistsForOrder($salesOrder->id)) {
                    $arInvoice = $this->arInvoiceService->createFromSalesOrder($salesOrder);

                    Log::info('AR Invoice auto-generated on delivery', [
                        'sales_order_id' => $salesOrder->id,
                        'sales_order_number' => $salesOrder->order_number,
                        'invoice_id' => $arInvoice->id,
                        'invoice_number' => $arInvoice->invoice_number,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't block the order update
                Log::error('Failed to auto-generate AR Invoice', [
                    'sales_order_id' => $salesOrder->id,
                    'sales_order_number' => $salesOrder->order_number,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Only update notes, keep status as 'not_invoiced' to allow retry
                // Note: 'failed' is not a valid enum value
                $salesOrder->updateQuietly([
                    'invoicing_notes' => 'Failed to create GL entry: ' . $e->getMessage(),
                ]);
            }
        }
    }
}
