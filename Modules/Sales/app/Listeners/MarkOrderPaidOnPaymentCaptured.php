<?php

namespace Modules\Sales\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\PaymentCaptured;
use Modules\Sales\Models\SalesOrder;

/**
 * DESIGN_ECOMMERCE_PAGO_STOCK (H-C): eslabon pago -> orden.
 *
 * El webhook de Stripe deja el rastro en payment_transactions y dispara
 * PaymentCaptured; antes de este listener NADIE escribia en la orden (el
 * dinero entraba y la orden no se enteraba). Aqui se marca payment_status y
 * con eso la orden empieza a COMPROMETER stock (CommittedStockService).
 *
 * La aplicacion contable del cobro a la AR NO ocurre aqui: la AR nace en la
 * entrega (camino unico R3) y CreateARInvoiceForSalesOrder aplica el cobro en
 * ese momento por el servicio de cobros de Finance.
 */
class MarkOrderPaidOnPaymentCaptured
{
    public function handle(PaymentCaptured $event): void
    {
        $transaction = $event->transaction;

        if (!$transaction->sales_order_id || $transaction->status !== 'captured') {
            return;
        }

        $order = SalesOrder::find($transaction->sales_order_id);
        if (!$order || $order->status === 'cancelled') {
            return;
        }

        // Idempotencia (segundo candado; el retry del webhook ya es no-op a
        // nivel transaccion): si ya esta paid, no reescribir.
        if ($order->payment_status === 'paid') {
            return;
        }

        // Conciliacion de monto: transaction.amount viene en PESOS (el sync
        // divide los centavos de Stripe entre 100). Un pago que no cuadra con
        // el total de la orden NO la marca pagada: queda rastro para
        // tratamiento manual (pagos parciales no existen en este flujo; si
        // aparece uno, es anomalia).
        $difference = abs((float) $transaction->amount - (float) $order->total_amount);
        if ($difference > 0.01) {
            $order->update([
                'metadata' => array_merge($order->metadata ?? [], [
                    'payment_mismatch' => [
                        'payment_transaction_id' => $transaction->id,
                        'payment_intent_id' => $transaction->payment_intent_id,
                        'transaction_amount' => (float) $transaction->amount,
                        'order_total' => (float) $order->total_amount,
                        'detected_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);
            Log::warning('MarkOrderPaidOnPaymentCaptured: monto del pago NO cuadra con la orden, no se marca paid', [
                'sales_order_id' => $order->id,
                'transaction_amount' => (float) $transaction->amount,
                'order_total' => (float) $order->total_amount,
            ]);
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => $transaction->captured_at ?? now(),
            'metadata' => array_merge($order->metadata ?? [], [
                'payment_transaction_id' => $transaction->id,
                'payment_intent_id' => $transaction->payment_intent_id,
            ]),
        ]);

        Log::info('MarkOrderPaidOnPaymentCaptured: orden marcada como pagada', [
            'sales_order_id' => $order->id,
            'payment_transaction_id' => $transaction->id,
        ]);
    }
}
