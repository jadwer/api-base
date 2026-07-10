<?php

namespace Modules\Commissions\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Commissions\Listeners\HandleARInvoiceFullyPaid;
use Modules\Commissions\Listeners\HandleARInvoicePaymentReversed;
use Modules\Finance\Events\ARInvoiceFullyPaid;
use Modules\Finance\Events\ARInvoicePaymentReversed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ARInvoiceFullyPaid::class => [
            HandleARInvoiceFullyPaid::class,
        ],
        ARInvoicePaymentReversed::class => [
            HandleARInvoicePaymentReversed::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * Explicit mapping above; discovery disabled to avoid double registration.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
