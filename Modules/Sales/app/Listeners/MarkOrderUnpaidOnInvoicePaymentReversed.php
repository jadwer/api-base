<?php

namespace Modules\Sales\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Finance\Events\ARInvoicePaymentReversed;
use Modules\Sales\Models\SalesOrder;

/**
 * Espejo de MarkOrderPaidOnInvoiceFullyPaid: si se revierte la aplicacion
 * del pago y la AR deja de estar liquidada, la orden vuelve a "no pagada",
 * salvo que el pago venga de una transaccion Stripe capturada (ese cobro
 * existe aunque en contabilidad se haya desaplicado; se corrige alla).
 */
class MarkOrderUnpaidOnInvoicePaymentReversed
{
    public function handle(ARInvoicePaymentReversed $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::find($invoice->sales_order_id);
        if (! $order || $order->payment_status !== 'paid') {
            return;
        }

        $hasCapturedTransaction = PaymentTransaction::where('sales_order_id', $order->id)
            ->where('status', 'captured')
            ->exists();
        if ($hasCapturedTransaction) {
            return;
        }

        $order->update([
            'payment_status' => 'unpaid',
            'paid_at' => null,
        ]);

        Log::info('MarkOrderUnpaidOnInvoicePaymentReversed: orden vuelve a no pagada', [
            'sales_order_id' => $order->id,
            'ar_invoice_id' => $invoice->id,
        ]);
    }
}
