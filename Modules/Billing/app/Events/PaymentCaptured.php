<?php

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\PaymentTransaction;

class PaymentCaptured
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PaymentTransaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param PaymentTransaction $transaction
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
