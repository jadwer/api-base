<?php

namespace Modules\Finance\Listeners;

use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrderCompletedListener
 *
 * Automatically creates an AR Invoice when a Sales Order is completed.
 *
 * Refactor ciclo (5b/F10): coexiste con SalesOrderDeliveredListener. NO es duplicacion
 * de factura (ambos son idempotentes por ar_invoice_id). Son dos PUNTOS DE ENTRADA:
 * - SalesOrderDelivered: camino del dashboard + entrega por remision (el flujo principal).
 * - SalesOrderCompleted: lo dispara Ecommerce\CheckoutService (flujo CheckoutSession) y
 *   EventReplayService. Se conserva para no romper ese flujo alternativo.
 * Cuando el checkout de ecommerce converja al flujo de entrega (reserva -> remision ->
 * deliver), este listener podra retirarse. Por ahora ambos conviven de forma segura.
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

        // Skip if invoice cannot be generated
        if (!$this->arInvoiceService->canGenerateInvoice($salesOrder)) {
            Log::info("SalesOrder does not meet conditions for AR Invoice generation", [
                'sales_order_id' => $salesOrder->id,
                'status' => $salesOrder->status,
            ]);
            return;
        }

        try {
            $arInvoice = $this->arInvoiceService->createFromSalesOrder($salesOrder);

            Log::info("AR Invoice created from SalesOrder via listener", [
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
}
