<?php

namespace Modules\Billing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Billing\Events\PaymentCaptured::class => [
            \Modules\Billing\Listeners\GenerateCFDIAfterPayment::class,
        ],
        // Future: CFDIGenerated event can trigger stamping, GL posting, email sending, etc.
        // \Modules\Billing\Events\CFDIGenerated::class => [
        //     \Modules\Billing\Listeners\StampCFDIWithPAC::class,
        //     \Modules\Billing\Listeners\PostCFDIToGeneralLedger::class,
        //     \Modules\Billing\Listeners\SendCFDIToCustomer::class,
        // ],
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
