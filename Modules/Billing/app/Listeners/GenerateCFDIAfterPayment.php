<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\PaymentCaptured;
use Modules\Billing\Events\CFDIGenerated;
use Modules\Billing\Services\CFDIAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateCFDIAfterPayment implements ShouldQueue
{
    use InteractsWithQueue;

    protected CFDIAutomationService $automationService;

    /**
     * Create the event listener.
     */
    public function __construct(CFDIAutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    /**
     * Handle the event.
     *
     * @param PaymentCaptured $event
     * @return void
     */
    public function handle(PaymentCaptured $event): void
    {
        $transaction = $event->transaction;

        Log::info('Processing PaymentCaptured event for CFDI generation', [
            'transaction_id' => $transaction->id,
            'gateway' => $transaction->gateway,
            'amount' => $transaction->amount,
        ]);

        try {
            // Auto-generate CFDI from payment transaction
            $cfdi = $this->automationService->generateFromPaymentTransaction($transaction);

            if ($cfdi) {
                Log::info('CFDI generated successfully after payment', [
                    'cfdi_id' => $cfdi->id,
                    'transaction_id' => $transaction->id,
                    'series' => $cfdi->series,
                    'folio' => $cfdi->folio,
                ]);

                // Dispatch CFDIGenerated event for further processing (stamping, GL posting, etc.)
                event(new CFDIGenerated($cfdi));
            } else {
                Log::warning('CFDI generation skipped', [
                    'transaction_id' => $transaction->id,
                    'reason' => 'No AR Invoice found or CFDI already exists',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate CFDI after payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw exception - allow payment to complete even if CFDI generation fails
            // CFDI can be generated manually later if needed
        }
    }

    /**
     * Handle a job failure.
     *
     * @param PaymentCaptured $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(PaymentCaptured $event, \Throwable $exception): void
    {
        Log::error('GenerateCFDIAfterPayment listener failed', [
            'transaction_id' => $event->transaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
