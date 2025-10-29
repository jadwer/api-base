<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\ShoppingCart;
use Carbon\Carbon;

class CleanupExpiredCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Delete carts that expired more than 7 days ago
        $expirationDate = now()->subDays(7);

        $expiredCarts = ShoppingCart::where('status', 'active')
            ->where('expires_at', '<', $expirationDate)
            ->get();

        $cleanedCount = 0;

        foreach ($expiredCarts as $cart) {
            try {
                // Delete cart items first
                $cart->cartItems()->delete();

                // Mark cart as expired
                $cart->update(['status' => 'expired']);

                $cleanedCount++;

            } catch (\Exception $e) {
                logger()->error('Failed to cleanup expired cart', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($cleanedCount > 0) {
            logger()->info('Cleaned up expired carts', [
                'count' => $cleanedCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }
}
