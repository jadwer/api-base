<?php

namespace Modules\Sales\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // DESIGN_ECOMMERCE_PAGO_STOCK (H-C): eslabon pago Stripe -> orden
        \Modules\Billing\Events\PaymentCaptured::class => [
            \Modules\Sales\Listeners\MarkOrderPaidOnPaymentCaptured::class,
        ],
        \Modules\Billing\Events\PaymentRefunded::class => [
            \Modules\Sales\Listeners\MarkOrderRefundedOnPaymentRefunded::class,
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
