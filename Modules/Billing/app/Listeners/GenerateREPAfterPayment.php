<?php

namespace Modules\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Services\CFDI\REPService;
use Modules\Finance\Events\ARPaymentApplied;

/**
 * Generates a Complemento de Pagos (REP) after each abono to a PPD invoice.
 *
 * Listens to ARPaymentApplied (fired for every abono). REPService applies the
 * guards (PUE / not timbrada / idempotency), so this listener is a thin adapter.
 *
 * ShouldQueue: on QUEUE=sync (apimb demo) it runs inline; on QUEUE=database
 * (LWM/staging) it requires the queue worker (hallazgo H1).
 */
class GenerateREPAfterPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private REPService $repService
    ) {}

    public function handle(ARPaymentApplied $event): void
    {
        try {
            $rep = $this->repService->generateFromPayment($event->payment);

            if ($rep) {
                Log::info('REP generated after payment', [
                    'payment_id' => $event->payment->id,
                    'cfdi_id' => $rep->id,
                    'status' => $rep->status,
                ]);
            }
        } catch (\Throwable $e) {
            // Never break the payment flow: a REP can be re-issued manually via
            // POST /ar-invoices/{invoice}/payment-complement.
            Log::error('GenerateREPAfterPayment failed', [
                'payment_id' => $event->payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ARPaymentApplied $event, \Throwable $exception): void
    {
        Log::error('GenerateREPAfterPayment listener failed', [
            'payment_id' => $event->payment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
