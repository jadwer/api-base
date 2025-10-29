<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Services\InventoryReservationService;

class CleanupExpiredInventoryReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(InventoryReservationService $inventoryReservationService): void
    {
        $cleanedCount = $inventoryReservationService->cleanupExpiredReservations();

        if ($cleanedCount > 0) {
            logger()->info('Cleaned up expired inventory reservations', [
                'count' => $cleanedCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }
}
