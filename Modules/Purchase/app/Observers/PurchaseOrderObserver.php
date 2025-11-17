<?php

namespace Modules\Purchase\Observers;

use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Events\PurchaseOrderReceived;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "updated" event.
     * Emit PurchaseOrderReceived event when status changes to 'received'
     */
    public function updated(PurchaseOrder $purchaseOrder): void
    {
        // Check if status was changed to 'received'
        if ($purchaseOrder->isDirty('status') && $purchaseOrder->status === 'received') {
            // Emit event for listeners (Finance: create AP Invoice, Inventory: create movements)
            event(new PurchaseOrderReceived($purchaseOrder));
        }
    }
}
