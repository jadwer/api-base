<?php

namespace Modules\Inventory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Events\InventoryMovementCreated;

/**
 * Retry GL Posting Job
 *
 * Retries failed GL postings for inventory movements
 */
class RetryGLPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $movementId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $movementId)
    {
        $this->movementId = $movementId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $movement = InventoryMovement::find($this->movementId);

        if (!$movement) {
            Log::warning('Inventory movement not found for GL retry', [
                'movement_id' => $this->movementId,
            ]);
            return;
        }

        // Only retry if still in error status
        if ($movement->gl_posting_status !== 'error') {
            Log::info('Inventory movement GL status changed, skipping retry', [
                'movement_id' => $this->movementId,
                'status' => $movement->gl_posting_status,
            ]);
            return;
        }

        Log::info('Retrying GL posting for inventory movement', [
            'movement_id' => $movement->id,
            'attempt' => ($movement->metadata['gl_retry_attempts'] ?? 0) + 1,
        ]);

        // Trigger the GL posting event again
        // The listener will handle incrementing retry count and scheduling next retry if needed
        event(new InventoryMovementCreated($movement));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GL retry job failed', [
            'movement_id' => $this->movementId,
            'error' => $exception->getMessage(),
        ]);
    }
}
