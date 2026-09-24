<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\PaymentCaptured;
use Modules\Finance\Services\ApplyCapturedPaymentToARInvoice;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Sales\Models\SalesOrder;

/**
 * Decision Gabino 2026-09-23 ("si a ambos, en el pago"): una orden pagada en
 * la tienda (Stripe) genera su cuenta por cobrar y su cobro EN EL MOMENTO
 * del pago, no hasta la entrega. Antes el dinero cobrado no existia en
 * Finanzas mientras la orden estuviera pendiente (E2E dev 18-sep, H2).
 *
 * No depende del orden de los listeners de PaymentCaptured: valida por su
 * cuenta que la transaccion sea captured y cuadre con el total de la orden
 * (misma tolerancia que MarkOrderPaidOnPaymentCaptured). Idempotente por
 * ar_invoice_id / invoiceExistsForOrder; al entregar, CreateARInvoiceForSalesOrder
 * ve la AR y no duplica. Interruptor: sales.auto_invoice_on_payment.
 */
class CreateARInvoiceOnPaymentCaptured
{
    public function __construct(
        private ARInvoiceService $arInvoiceService,
        private ApplyCapturedPaymentToARInvoice $applier,
    ) {}

    public function handle(PaymentCaptured $event): void
    {
        $transaction = $event->transaction;

        if (! config('sales.auto_invoice_on_payment', true)) {
            return;
        }

        if (! $transaction->sales_order_id || $transaction->status !== 'captured') {
            return;
        }

        $order = SalesOrder::find($transaction->sales_order_id);
        if (! $order || $order->status === 'cancelled') {
            return;
        }

        if ($order->ar_invoice_id || $this->arInvoiceService->invoiceExistsForOrder($order->id)) {
            return;
        }

        if (abs((float) $transaction->amount - (float) $order->total_amount) > 0.01) {
            Log::warning('CreateARInvoiceOnPaymentCaptured: monto del pago no cuadra con la orden, no se factura', [
                'sales_order_id' => $order->id,
                'transaction_amount' => (float) $transaction->amount,
                'order_total' => (float) $order->total_amount,
            ]);

            return;
        }

        try {
            $arInvoice = $this->arInvoiceService->createFromPaidSalesOrder($order);

            Log::info('AR Invoice creada al capturar el pago', [
                'sales_order_id' => $order->id,
                'ar_invoice_id' => $arInvoice->id,
                'payment_intent_id' => $transaction->payment_intent_id,
            ]);

            $this->applier->apply($order, $arInvoice, $transaction, 'Cobro Stripe aplicado automaticamente al capturar el pago');
        } catch (\Throwable $e) {
            Log::error('No se pudo crear la AR al capturar el pago; se intentara en la entrega', [
                'sales_order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $order->updateQuietly([
                'invoicing_notes' => trim(($order->invoicing_notes ?? '') .
                    ' | AR no creada al pagar (' . $e->getMessage() . '); se intentara al entregar.'),
            ]);
        }
    }
}
