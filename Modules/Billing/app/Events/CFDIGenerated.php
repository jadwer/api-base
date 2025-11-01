<?php

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\CFDIInvoice;

class CFDIGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CFDIInvoice $invoice;

    /**
     * Create a new event instance.
     *
     * @param CFDIInvoice $invoice
     */
    public function __construct(CFDIInvoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
