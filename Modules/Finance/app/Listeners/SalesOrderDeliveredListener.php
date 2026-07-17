<?php

namespace Modules\Finance\Listeners;

use Modules\Sales\Events\SalesOrderDelivered;
use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrderDeliveredListener
 *
 * Refactor ciclo (Patron 2, decision D-c): crea la ARInvoice cuando la venta se
 * entrega. Reemplaza al SalesOrderObserver::updated (que hacia esto acoplado al PATCH
 * de status) como camino UNICO del dashboard. Ecommerce dispara el mismo evento.
 * Idempotente por ar_invoice_id (no duplica si el evento se reenvia).
 */
class SalesOrderDeliveredListener
{
    public function __construct(
        private ARInvoiceService $arInvoiceService
    ) {}

    public function handle(SalesOrderDelivered $event): void
    {
        $salesOrder = $event->salesOrder;

        if (!config('sales.auto_invoice_on_delivery', true)) {
            Log::info('Auto-invoicing disabled, skipping AR Invoice creation', [
                'sales_order_id' => $salesOrder->id,
            ]);
            return;
        }

        // Idempotencia: ya facturada.
        if ($salesOrder->ar_invoice_id || $this->arInvoiceService->invoiceExistsForOrder($salesOrder->id)) {
            Log::info('SalesOrder already has AR Invoice', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $salesOrder->ar_invoice_id,
            ]);
            return;
        }

        try {
            $arInvoice = $this->arInvoiceService->createFromSalesOrder($salesOrder);

            Log::info('AR Invoice auto-generated on delivery (SalesOrderDelivered)', [
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->order_number,
                'ar_invoice_id' => $arInvoice->id,
                'ar_invoice_number' => $arInvoice->invoice_number,
                'total_amount' => $arInvoice->total_amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto-generate AR Invoice on delivery', [
                'sales_order_id' => $salesOrder->id,
                'error' => $e->getMessage(),
            ]);

            // No bloquear la entrega: la factura puede crearse manualmente despues.
            $salesOrder->updateQuietly([
                'invoicing_notes' => 'Failed to create AR Invoice: ' . $e->getMessage(),
            ]);
        }
    }
}
