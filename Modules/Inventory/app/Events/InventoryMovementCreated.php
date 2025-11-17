<?php

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\InventoryMovement;

/**
 * IV-010: Inventory Movement Created Event
 *
 * Fired when an inventory movement is created
 * Used to trigger GL posting
 */
class InventoryMovementCreated
{
    use Dispatchable, SerializesModels;

    public InventoryMovement $movement;

    /**
     * Create a new event instance.
     */
    public function __construct(InventoryMovement $movement)
    {
        $this->movement = $movement;
    }
}
