<?php

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Listen to Purchase module events for inventory integration
        \Modules\Purchase\Events\PurchaseOrderReceived::class => [
            \Modules\Inventory\Listeners\PurchaseOrderReceivedListener::class,
        ],

        // Refactor ciclo (Patron 2): cancelar una OC recibida revierte la entrada de stock.
        \Modules\Purchase\Events\PurchaseOrderCancelled::class => [
            \Modules\Inventory\Listeners\PurchaseOrderCancelledListener::class,
        ],

        // IV-010: Listen to inventory movements for GL integration
        \Modules\Inventory\Events\InventoryMovementCreated::class => [
            \Modules\Inventory\Listeners\PostInventoryMovementToGL::class,
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
