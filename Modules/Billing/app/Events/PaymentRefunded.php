<?php

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\PaymentTransaction;

/**
 * DESIGN_ECOMMERCE_PAGO_STOCK (H-C): espejo de PaymentCaptured para
 * charge.refunded. Sales lo escucha para marcar la orden como refunded.
 * La reversa operativa (cancelar orden, reponer stock) NO es automatica:
 * sigue el flujo de cancelacion/devolucion existente, decision humana.
 */
class PaymentRefunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PaymentTransaction $transaction;

    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
