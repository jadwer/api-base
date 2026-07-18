<?php

namespace Modules\Sales\Observers;

use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrderObserver
 *
 * SA-M001: Automatically creates AR Invoice when Sales Order is delivered
 */
class SalesOrderObserver
{
    public function __construct(
        private ARInvoiceService $arInvoiceService
    ) {}

    /**
     * Handle the SalesOrder "updated" event.
     *
     * Refactor ciclo (Patron 2, decision D-c): NEUTRALIZADO. Antes este observer creaba
     * la ARInvoice al detectar status->delivered. Eso era uno de TRES caminos de
     * facturacion inconsistentes (observer + evento ecommerce + fallback en Billing) y
     * ademas se disparaba con cualquier PATCH a 'delivered' (que ya bloqueamos en el
     * Schema). El camino UNICO ahora es el evento SalesOrderDelivered ->
     * Finance\CreateARInvoiceForSalesOrder, disparado explicitamente desde el servicio de
     * entrega (RemissionController::deliver / CheckoutService).
     *
     * Se deja el observer registrado pero sin efecto de facturacion, por si se quiere
     * enganchar aqui logica futura ligada al ciclo de vida del modelo (no de dominio).
     */
    public function updated(SalesOrder $salesOrder): void
    {
        // Intencionalmente vacio. La facturacion en entrega la maneja
        // Finance\CreateARInvoiceForSalesOrder via el evento SalesOrderDelivered.
    }
}
