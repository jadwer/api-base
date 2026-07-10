<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\ARInvoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ARInvoicePaymentReversed Event
 *
 * Triggered when a payment application is reversed and the AR Invoice stops
 * being fully paid (status leaves 'paid' inside
 * PaymentApplicationService::unapplyPayment).
 */
class ARInvoicePaymentReversed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ARInvoice $invoice;

    /**
     * Create a new event instance.
     */
    public function __construct(ARInvoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
