<?php

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\CFDIInvoice;

class CFDICancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CFDIInvoice $invoice;

    public function __construct(CFDIInvoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
