<?php

namespace Modules/Purchase\Events;

use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PurchaseOrderReceived Event
 *
 * Triggered when a Purchase Order is received and ready for AP Invoice creation
 */
class PurchaseOrderReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PurchaseOrder $purchaseOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }
}
