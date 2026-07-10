<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\ARInvoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ARInvoiceFullyPaid Event
 *
 * Triggered when an AR Invoice becomes fully paid (status transitions to
 * 'paid' inside PaymentApplicationService::applyPayment).
 */
class ARInvoiceFullyPaid
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
