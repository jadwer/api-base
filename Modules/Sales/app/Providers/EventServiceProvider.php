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
        // Decision 2026-09-23: el cobro de la AR (Cuentas por Cobrar) propaga a la orden.
        \Modules\Finance\Events\ARInvoiceFullyPaid::class => [
            \Modules\Sales\Listeners\MarkOrderPaidOnInvoiceFullyPaid::class,
        ],
        \Modules\Finance\Events\ARInvoicePaymentReversed::class => [
            \Modules\Sales\Listeners\MarkOrderUnpaidOnInvoicePaymentReversed::class,
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
