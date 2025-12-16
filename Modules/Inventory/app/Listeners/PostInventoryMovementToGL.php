<?php

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Inventory\Events\InventoryMovementCreated;
use Modules\Accounting\Services\AccountingService;
use Modules\Accounting\Models\Account;

/**
 * IV-010: Post Inventory Movements to GL
 *
 * Automatically creates GL journal entries for inventory movements:
 * - Entry: DR Inventory Asset / CR Inventory Accrual
 * - Exit: DR COGS / CR Inventory Asset
 * - Adjustment: DR/CR Inventory Asset, DR/CR Inventory Variance
 * - Transfer: No GL posting if within same account
 */
class PostInventoryMovementToGL
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the event
     */
    public function handle(InventoryMovementCreated $event): void
    {
        $movement = $event->movement;

        // Skip if already posted to GL
        if ($movement->gl_journal_entry_id) {
            Log::info('Inventory movement already posted to GL', [
                'movement_id' => $movement->id,
            ]);
            return;
        }

        try {
            // Create GL entry based on movement type
            $journalEntry = match ($movement->movement_type) {
                'entry' => $this->postEntryMovement($movement),
                'exit' => $this->postExitMovement($movement),
                'adjustment' => $this->postAdjustmentMovement($movement),
                'transfer' => $this->postTransferMovement($movement),
                default => null,
            };

            if ($journalEntry) {
                // Update movement with GL posting info
                $movement->update([
                    'gl_journal_entry_id' => $journalEntry->id,
                    'gl_posting_status' => 'posted',
                    'gl_posting_notes' => "Posted to GL Entry #{$journalEntry->entry_number}",
                ]);

                Log::info('Inventory movement posted to GL', [
                    'movement_id' => $movement->id,
                    'journal_entry_id' => $journalEntry->id,
                    'movement_type' => $movement->movement_type,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the movement creation
            Log::error('Failed to post inventory movement to GL', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update movement with failed status
            $metadata = $movement->metadata ?? [];
            $retryAttempts = ($metadata['gl_retry_attempts'] ?? 0) + 1;
            $metadata['gl_retry_attempts'] = $retryAttempts;
            $metadata['last_error'] = $e->getMessage();
            $metadata['last_attempt_at'] = now()->toDateTimeString();

            $movement->update([
                'gl_posting_status' => 'error',
                'gl_posting_notes' => "GL Posting Failed (Attempt $retryAttempts): {$e->getMessage()}",
                'metadata' => $metadata,
            ]);

            // Queue retry if enabled and max attempts not reached
            $retryEnabled = config('inventory.retry.enabled', true);
            $maxAttempts = config('inventory.retry.max_attempts', 3);

            if ($retryEnabled && $retryAttempts < $maxAttempts) {
                $backoffSeconds = config('inventory.retry.backoff_seconds', [60, 300, 900]);
                $delay = $backoffSeconds[$retryAttempts - 1] ?? 900; // Default to 15 min

                Log::info('Scheduling GL posting retry', [
                    'movement_id' => $movement->id,
                    'attempt' => $retryAttempts,
                    'delay_seconds' => $delay,
                ]);

                // Dispatch retry after delay
                \Modules\Inventory\Jobs\RetryGLPosting::dispatch($movement->id)
                    ->delay(now()->addSeconds($delay));
            } elseif ($retryAttempts >= $maxAttempts) {
                // Max retries reached - notify accounting
                $this->notifyFailedPosting($movement, $e);
            }
        }
    }

    /**
     * Post entry movement (purchase/receipt)
     * DR: Inventory Asset / CR: Inventory Accrual
     */
    private function postEntryMovement($movement)
    {
        $amount = abs($movement->total_cost ?? ($movement->quantity * $movement->cost_per_unit));

        // Get account codes from config
        $inventoryAssetCode = $this->getWarehouseAccount($movement->warehouse_id, 'inventory_asset')
            ?? config('inventory.gl_accounts.inventory_asset', '115-001');
        $inventoryAccrualCode = config('inventory.gl_accounts.inventory_accrual', '205-001');

        // Find accounts by code
        $inventoryAsset = Account::where('code', $inventoryAssetCode)->first();
        $inventoryAccrual = Account::where('code', $inventoryAccrualCode)->first();

        if (!$inventoryAsset || !$inventoryAccrual) {
            throw new \Exception("Required GL accounts not found for entry movement (Asset: $inventoryAssetCode, Accrual: $inventoryAccrualCode)");
        }

        return $this->accountingService->createJournalEntry(
            'GL', // General Ledger journal
            $movement->movement_date->format('Y-m-d'),
            "Inventory Entry - Movement #{$movement->id}",
            "INV-{$movement->id}",
            [
                [
                    'account_id' => $inventoryAsset->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => "Stock increase - {$movement->quantity} units @ {$movement->cost_per_unit}",
                ],
                [
                    'account_id' => $inventoryAccrual->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => "Inventory accrual",
                ],
            ]
        );
    }

    /**
     * Post exit movement (sale/delivery)
     * DR: COGS / CR: Inventory Asset
     */
    private function postExitMovement($movement)
    {
        $amount = abs($movement->total_cost ?? ($movement->quantity * $movement->cost_per_unit));

        // Get account codes from config
        $cogsCode = $this->getWarehouseAccount($movement->warehouse_id, 'cogs')
            ?? config('inventory.gl_accounts.cogs', '601-001');
        $inventoryAssetCode = $this->getWarehouseAccount($movement->warehouse_id, 'inventory_asset')
            ?? config('inventory.gl_accounts.inventory_asset', '115-001');

        // Find accounts
        $cogs = Account::where('code', $cogsCode)->first();
        $inventoryAsset = Account::where('code', $inventoryAssetCode)->first();

        if (!$cogs || !$inventoryAsset) {
            throw new \Exception("Required GL accounts not found for exit movement (COGS: $cogsCode, Asset: $inventoryAssetCode)");
        }

        return $this->accountingService->createJournalEntry(
            'GL',
            $movement->movement_date->format('Y-m-d'),
            "Inventory Exit - Movement #{$movement->id}",
            "INV-{$movement->id}",
            [
                [
                    'account_id' => $cogs->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => "Cost of goods sold - {$movement->quantity} units",
                ],
                [
                    'account_id' => $inventoryAsset->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => "Stock decrease",
                ],
            ]
        );
    }

    /**
     * Post adjustment movement
     * DR/CR: Inventory Asset / DR/CR: Inventory Variance
     */
    private function postAdjustmentMovement($movement)
    {
        $amount = abs($movement->total_cost ?? ($movement->quantity * $movement->cost_per_unit));

        // Get account codes from config
        $inventoryAssetCode = $this->getWarehouseAccount($movement->warehouse_id, 'inventory_asset')
            ?? config('inventory.gl_accounts.inventory_asset', '115-001');
        $inventoryVarianceCode = config('inventory.gl_accounts.inventory_variance', '801-001');

        // Find accounts
        $inventoryAsset = Account::where('code', $inventoryAssetCode)->first();
        $inventoryVariance = Account::where('code', $inventoryVarianceCode)->first();

        if (!$inventoryAsset || !$inventoryVariance) {
            throw new \Exception("Required GL accounts not found for adjustment movement (Asset: $inventoryAssetCode, Variance: $inventoryVarianceCode)");
        }

        // Positive adjustment (increase stock)
        if ($movement->quantity > 0) {
            return $this->accountingService->createJournalEntry(
                'GL',
                $movement->movement_date->format('Y-m-d'),
                "Inventory Adjustment (Increase) - Movement #{$movement->id}",
                "INV-{$movement->id}",
                [
                    [
                        'account_id' => $inventoryAsset->id,
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => "Inventory increase adjustment",
                    ],
                    [
                        'account_id' => $inventoryVariance->id,
                        'debit_amount' => 0,
                        'credit_amount' => $amount,
                        'description' => "Inventory variance (gain)",
                    ],
                ],
            );
        }

        // Negative adjustment (decrease stock)
        return $this->accountingService->createJournalEntry(
            'GL',
            $movement->movement_date->format('Y-m-d'),
            "Inventory Adjustment (Decrease) - Movement #{$movement->id}",
            "INV-{$movement->id}",
            [
                [
                    'account_id' => $inventoryVariance->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => "Inventory variance (loss)",
                ],
                [
                    'account_id' => $inventoryAsset->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => "Inventory decrease adjustment",
                ],
            ]
        );
    }

    /**
     * Post transfer movement
     * Only creates GL entry if transferring between different warehouse accounts
     */
    private function postTransferMovement($movement)
    {
        // For now, skip GL posting for transfers
        // In a full implementation, you would check if warehouses have different GL accounts
        Log::info('Transfer movement - GL posting skipped (internal transfer)', [
            'movement_id' => $movement->id,
        ]);

        $movement->update([
            'gl_posting_status' => 'posted', // Transfers don't require GL posting
            'gl_posting_notes' => 'Internal transfer - no GL posting required',
        ]);

        return null;
    }

    /**
     * Get warehouse-specific GL account code
     *
     * @param int|null $warehouseId
     * @param string $accountType (inventory_asset, cogs, etc.)
     * @return string|null
     */
    private function getWarehouseAccount(?int $warehouseId, string $accountType): ?string
    {
        if (!$warehouseId) {
            return null;
        }

        $warehouseAccounts = config('inventory.gl_accounts.warehouse_accounts', []);

        return $warehouseAccounts[$warehouseId][$accountType] ?? null;
    }

    /**
     * Notify accounting team of failed GL posting after max retries
     *
     * @param $movement
     * @param \Exception $e
     * @return void
     */
    private function notifyFailedPosting($movement, \Exception $e): void
    {
        if (!config('inventory.retry.notify_on_failure', true)) {
            return;
        }

        Log::critical('GL posting failed after max retries', [
            'movement_id' => $movement->id,
            'movement_type' => $movement->movement_type,
            'quantity' => $movement->quantity,
            'total_cost' => $movement->total_cost,
            'error' => $e->getMessage(),
            'retry_attempts' => $movement->metadata['gl_retry_attempts'] ?? 0,
        ]);

        // TODO: Send email notification to accounting team
        // $email = config('inventory.retry.notification_email');
        // Mail::to($email)->send(new GLPostingFailed($movement, $e));
    }
}
