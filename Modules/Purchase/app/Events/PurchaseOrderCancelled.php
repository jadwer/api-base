<?php

namespace Modules\Purchase\Events;

use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PurchaseOrderCancelled Event
 *
 * Refactor ciclo (Patron 2): antes no existia. Cancelar una OC 'received' dejaba
 * stock fantasma y una APInvoice viva sin revertir. Este evento lo escuchan los
 * listeners de Inventory (revierte la entrada de stock) y Finance (anula la
 * APInvoice), simetricos a los de PurchaseOrderReceived.
 *
 * Lleva el status previo para que los listeners sepan si habia efectos que revertir
 * (solo una OC que estuvo en 'received' genero stock/APInvoice).
 */
class PurchaseOrderCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PurchaseOrder $purchaseOrder;
    public string $previousStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, string $previousStatus)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->previousStatus = $previousStatus;
    }
}
