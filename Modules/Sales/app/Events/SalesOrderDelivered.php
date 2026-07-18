<?php

namespace Modules\Sales\Events;

use Modules\Sales\Models\SalesOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SalesOrderDelivered Event
 *
 * Refactor ciclo (Patron 2, decision D-c): evento de dominio UNICO para "la venta se
 * entrego". Antes la creacion de la ARInvoice tenia TRES caminos inconsistentes:
 *   1. SalesOrderObserver::updated cuando status->delivered (flujo dashboard).
 *   2. SalesOrderCompleted disparado solo por Ecommerce\CheckoutService (online).
 *   3. fallback en Billing\CFDIInvoiceController::createFromOrder.
 * Ahora dashboard y ecommerce convergen a este evento, disparado desde el servicio de
 * entrega (RemissionController::deliver / CheckoutService). Un solo camino, un solo
 * listener (Finance\CreateARInvoiceForSalesOrder, R3) que crea la ARInvoice.
 */
class SalesOrderDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SalesOrder $salesOrder;

    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }
}
