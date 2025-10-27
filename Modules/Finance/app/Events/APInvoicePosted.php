<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\APInvoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * APInvoicePosted Event
 *
 * Triggered when an AP Invoice is posted to the General Ledger
 */
class APInvoicePosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public APInvoice $invoice;

    /**
     * Create a new event instance.
     */
    public function __construct(APInvoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
