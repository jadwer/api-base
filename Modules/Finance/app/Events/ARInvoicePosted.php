<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\ARInvoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ARInvoicePosted Event
 *
 * Triggered when an AR Invoice is posted to the General Ledger
 */
class ARInvoicePosted
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
