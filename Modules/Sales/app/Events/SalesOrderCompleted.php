<?php

namespace Modules\Sales\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class SalesOrderCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SalesOrder $salesOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }
}
