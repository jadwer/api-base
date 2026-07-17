<?php

namespace Modules\Finance\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Sales → Finance Integration
        \Modules\Sales\Events\SalesOrderCompleted::class => [
            \Modules\Finance\Listeners\SalesOrderCompletedListener::class,
        ],

        // Refactor ciclo (Patron 2, D-c): camino UNICO de facturacion de venta.
        // Reemplaza al SalesOrderObserver::updated. Dashboard y ecommerce disparan este.
        \Modules\Sales\Events\SalesOrderDelivered::class => [
            \Modules\Finance\Listeners\SalesOrderDeliveredListener::class,
        ],

        // Purchase → Finance Integration
        \Modules\Purchase\Events\PurchaseOrderReceived::class => [
            \Modules\Finance\Listeners\PurchaseOrderReceivedListener::class,
        ],

        // Sales → Finance Cancellation
        \Modules\Sales\Events\SalesOrderCancelled::class => [
            \Modules\Finance\Listeners\SalesOrderCancelledListener::class,
        ],

        // Refactor ciclo (Patron 2): cancelar una OC recibida anula su APInvoice.
        \Modules\Purchase\Events\PurchaseOrderCancelled::class => [
            \Modules\Finance\Listeners\PurchaseOrderCancelledListener::class,
        ],

        // Finance Internal Events
        \Modules\Finance\Events\ARInvoicePosted::class => [
            \Modules\Finance\Listeners\ARInvoicePostedListener::class,
        ],
        \Modules\Finance\Events\APInvoicePosted::class => [
            \Modules\Finance\Listeners\APInvoicePostedListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
