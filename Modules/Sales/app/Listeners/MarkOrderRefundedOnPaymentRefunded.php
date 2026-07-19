<?php

namespace Modules\Sales\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\PaymentRefunded;
use Modules\Sales\Models\SalesOrder;

/**
 * DESIGN_ECOMMERCE_PAGO_STOCK (H-C): espejo del mark-paid para charge.refunded.
 *
 * Marca payment_status='refunded' (con eso la orden deja de comprometer stock).
 * NO cancela la orden ni repone inventario en automatico: la reversa operativa
 * (cancelar, devolver, nota de credito) sigue el flujo existente y es decision
 * humana; aqui solo se refleja el hecho del reembolso con su rastro.
 */
class MarkOrderRefundedOnPaymentRefunded
{
    public function handle(PaymentRefunded $event): void
    {
        $transaction = $event->transaction;

        if (!$transaction->sales_order_id) {
            return;
        }

        $order = SalesOrder::find($transaction->sales_order_id);
        if (!$order || $order->payment_status === 'refunded') {
            return;
        }

        $order->update([
            'payment_status' => 'refunded',
            'metadata' => array_merge($order->metadata ?? [], [
                'refunded_at' => now()->toIso8601String(),
                'refund_transaction_id' => $transaction->id,
            ]),
        ]);

        Log::info('MarkOrderRefundedOnPaymentRefunded: orden marcada como reembolsada', [
            'sales_order_id' => $order->id,
            'payment_transaction_id' => $transaction->id,
        ]);
    }
}
