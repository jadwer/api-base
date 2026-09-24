<?php

namespace Modules\Sales\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\ARInvoiceFullyPaid;
use Modules\Sales\Models\SalesOrder;

/**
 * Decision Gabino 2026-09-23: cuando la cuenta por cobrar de una orden queda
 * totalmente pagada (cobro registrado en Cuentas por Cobrar, por el camino
 * que sea), la orden se marca pagada. Antes solo los listeners de Stripe
 * escribian payment_status y un cobro manual dejaba la orden "no pagada"
 * (E2E dev 18-sep, H1).
 */
class MarkOrderPaidOnInvoiceFullyPaid
{
    public function handle(ARInvoiceFullyPaid $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::find($invoice->sales_order_id);
        if (! $order || $order->status === 'cancelled' || $order->payment_status === 'paid') {
            return;
        }

        // Si el cobro viene de Stripe, la fecha real es la de la captura.
        $capturedAt = \Modules\Billing\Models\PaymentTransaction::where('sales_order_id', $order->id)
            ->where('status', 'captured')
            ->latest('captured_at')
            ->value('captured_at');

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => $capturedAt ?? $invoice->paid_date ?? now(),
            'metadata' => array_merge($order->metadata ?? [], [
                'paid_via_ar_invoice_id' => $invoice->id,
            ]),
        ]);

        Log::info('MarkOrderPaidOnInvoiceFullyPaid: orden marcada como pagada por su AR', [
            'sales_order_id' => $order->id,
            'ar_invoice_id' => $invoice->id,
        ]);
    }
}
