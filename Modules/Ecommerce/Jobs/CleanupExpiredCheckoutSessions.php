<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Services\CheckoutService;
use Carbon\Carbon;

class CleanupExpiredCheckoutSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(CheckoutService $checkoutService): void
    {
        $expiredSessions = CheckoutSession::expired()->get();

        $cleanedCount = 0;

        foreach ($expiredSessions as $session) {
            try {
                // Release inventory reservations
                $checkoutService->releaseInventory($session);

                // Mark session as expired
                $session->markAsExpired();

                $cleanedCount++;

            } catch (\Exception $e) {
                logger()->error('Failed to cleanup expired checkout session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($cleanedCount > 0) {
            logger()->info('Cleaned up expired checkout sessions', [
                'count' => $cleanedCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }
}
