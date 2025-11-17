<?php

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Events\InventoryMovementCreated;

/**
 * Repost Inventory Movements to GL
 *
 * This command allows manual reposting of failed GL entries
 * for inventory movements.
 *
 * Usage:
 *   php artisan inventory:repost-gl              # Repost all failed movements
 *   php artisan inventory:repost-gl --id=123     # Repost specific movement
 *   php artisan inventory:repost-gl --limit=10   # Repost first 10 failed
 */
class RepostInventoryGLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:repost-gl
                            {--id= : Specific movement ID to repost}
                            {--limit=50 : Maximum number of movements to repost (default: 50)}
                            {--force : Force repost even if not in failed status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repost failed inventory movements to GL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Inventory GL Reposting Tool');
        $this->newLine();

        $movementId = $this->option('id');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        if ($movementId) {
            return $this->repostSingle($movementId, $force);
        }

        return $this->repostMultiple($limit, $force);
    }

    /**
     * Repost a single movement
     */
    private function repostSingle(int $movementId, bool $force): int
    {
        $movement = InventoryMovement::find($movementId);

        if (!$movement) {
            $this->error("❌ Movement ID {$movementId} not found.");
            return 1;
        }

        $this->info("Movement #{$movement->id} - {$movement->movement_type}");
        $this->line("  Status: {$movement->gl_posting_status}");
        $this->line("  Quantity: {$movement->quantity}");
        $this->line("  Total Cost: {$movement->total_cost}");
        $this->newLine();

        if ($movement->gl_posting_status === 'posted' && !$force) {
            $this->warn('⚠️  Movement already posted to GL. Use --force to repost.');
            return 0;
        }

        if (!$this->confirm('Repost this movement to GL?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        return $this->executeRepost($movement);
    }

    /**
     * Repost multiple failed movements
     */
    private function repostMultiple(int $limit, bool $force): int
    {
        $query = InventoryMovement::query();

        if (!$force) {
            $query->where('gl_posting_status', 'failed');
        }

        $movements = $query->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($movements->isEmpty()) {
            $this->info('✅ No failed movements found.');
            return 0;
        }

        $this->info("Found {$movements->count()} movement(s) to repost.");
        $this->newLine();

        $this->table(
            ['ID', 'Type', 'Quantity', 'Cost', 'Status', 'Attempts'],
            $movements->map(function ($m) {
                return [
                    $m->id,
                    $m->movement_type,
                    $m->quantity,
                    number_format($m->total_cost ?? 0, 2),
                    $m->gl_posting_status,
                    $m->metadata['gl_retry_attempts'] ?? 0,
                ];
            })
        );

        $this->newLine();

        if (!$this->confirm("Repost these {$movements->count()} movement(s)?", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $successCount = 0;
        $failCount = 0;

        $progressBar = $this->output->createProgressBar($movements->count());
        $progressBar->start();

        foreach ($movements as $movement) {
            $result = $this->executeRepost($movement, false);
            if ($result === 0) {
                $successCount++;
            } else {
                $failCount++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully reposted: {$successCount}");
        if ($failCount > 0) {
            $this->error("❌ Failed to repost: {$failCount}");
        }

        return $failCount > 0 ? 1 : 0;
    }

    /**
     * Execute the repost for a movement
     */
    private function executeRepost(InventoryMovement $movement, bool $showOutput = true): int
    {
        try {
            // Reset retry counter
            $metadata = $movement->metadata ?? [];
            $metadata['gl_retry_attempts'] = 0;
            $metadata['manual_repost_at'] = now()->toDateTimeString();
            $metadata['manual_repost_by'] = 'artisan command';

            $movement->update([
                'gl_posting_status' => null,
                'gl_journal_entry_id' => null,
                'gl_posting_notes' => 'Manually triggered repost',
                'metadata' => $metadata,
            ]);

            // Trigger GL posting
            event(new InventoryMovementCreated($movement));

            // Refresh to get updated status
            $movement->refresh();

            if ($showOutput) {
                if ($movement->gl_posting_status === 'posted') {
                    $this->info("✅ Successfully reposted movement #{$movement->id}");
                    $this->line("   Journal Entry ID: {$movement->gl_journal_entry_id}");
                } else {
                    $this->error("❌ Failed to repost movement #{$movement->id}");
                    $this->line("   Status: {$movement->gl_posting_status}");
                    $this->line("   Notes: {$movement->gl_posting_notes}");
                }
            }

            return $movement->gl_posting_status === 'posted' ? 0 : 1;

        } catch (\Exception $e) {
            if ($showOutput) {
                $this->error("❌ Error reposting movement #{$movement->id}");
                $this->error("   {$e->getMessage()}");
            }
            return 1;
        }
    }
}
