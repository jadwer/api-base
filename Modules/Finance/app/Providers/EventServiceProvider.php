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

        // Purchase → Finance Integration
        \Modules\Purchase\Events\PurchaseOrderReceived::class => [
            \Modules\Finance\Listeners\PurchaseOrderReceivedListener::class,
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
