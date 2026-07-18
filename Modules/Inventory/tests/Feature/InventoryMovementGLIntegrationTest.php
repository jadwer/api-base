<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalPeriod;

/**
 * IV-010: Inventory Movement GL Integration Tests
 *
 * Tests that inventory movements automatically create GL journal entries
 */
class InventoryMovementGLIntegrationTest extends TestCase
{
    /**
     * Create required GL infrastructure for inventory posting:
     * - GL Journal (required by AccountingService)
     * - Open FiscalPeriod for current month (required by AccountingService)
     * - GL Accounts referenced in PostInventoryMovementToGL listener
     */
    protected function createRequiredGLAccounts(): array
    {
        // Create GL Journal (required by AccountingService::createJournalEntry)
        Journal::firstOrCreate(
            ['code' => 'GL'],
            [
                'name' => 'General Ledger',
                'description' => 'General Ledger Journal',
                'prefix' => 'GL',
                'type' => 'general',
                'status' => 'active',
                'metadata' => [],
            ]
        );

        // Create open FiscalPeriod for current month (required by AccountingService)
        $now = now();
        FiscalPeriod::firstOrCreate(
            ['year' => $now->year, 'month' => $now->month],
            [
                'name' => $now->format('Y-m'),
                'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
                'metadata' => [],
            ]
        );

        // Inventory Asset (115-001)
        $inventoryAsset = Account::firstOrCreate(
            ['code' => '115-001'],
            [
                'name' => 'Inventory Asset',
                'account_type' => 'asset',
                'nature' => 'debit',
                'level' => 2,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ]
        );

        // Inventory Accrual (205-001)
        $inventoryAccrual = Account::firstOrCreate(
            ['code' => '205-001'],
            [
                'name' => 'Inventory Accrual',
                'account_type' => 'liability',
                'nature' => 'credit',
                'level' => 2,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ]
        );

        // COGS - use code from config (default 500-001)
        $cogsCode = config('inventory.gl_accounts.cogs', '500-001');
        $cogs = Account::firstOrCreate(
            ['code' => $cogsCode],
            [
                'name' => 'Cost of Goods Sold',
                'account_type' => 'expense',
                'nature' => 'debit',
                'level' => 2,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ]
        );

        // Inventory Variance - use code from config (default 680-001)
        $varianceCode = config('inventory.gl_accounts.inventory_variance', '680-001');
        $inventoryVariance = Account::firstOrCreate(
            ['code' => $varianceCode],
            [
                'name' => 'Inventory Variance',
                'account_type' => 'expense',
                'nature' => 'debit',
                'level' => 2,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ]
        );

        return [
            'inventory_asset' => $inventoryAsset,
            'inventory_accrual' => $inventoryAccrual,
            'cogs' => $cogs,
            'inventory_variance' => $inventoryVariance,
        ];
    }
    /**
     * Test entry movement creates GL journal entry
     * DR: Inventory Asset / CR: Inventory Accrual
     */
    public function test_entry_movement_creates_gl_journal_entry(): void
    {
        // Arrange: Create required GL accounts
        $this->createRequiredGLAccounts();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        // Get count of journal entries before
        $initialCount = JournalEntry::count();

        // Act: Create entry movement
        // QA post-commit: el listener GL lee total_value (= quantity * unit_cost,
        // columna generada por BD), no total_cost/cost_per_unit. Los fixtures de este
        // archivo pueblan unit_cost por eso.
        $movement = InventoryMovement::create([
            'movement_type' => 'entry',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 5000.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'purchase',
        ]);

        // Assert: Journal entry was created
        $this->assertEquals($initialCount + 1, JournalEntry::count());

        // Assert: Movement linked to GL entry
        $movement->refresh();
        $this->assertNotNull($movement->gl_journal_entry_id);
        $this->assertEquals('posted', $movement->gl_posting_status);

        // Assert: Journal entry has correct lines
        $journalEntry = JournalEntry::find($movement->gl_journal_entry_id);
        $this->assertNotNull($journalEntry);
        $this->assertCount(2, $journalEntry->journalLines);

        // Check debits and credits balance
        $this->assertEquals($journalEntry->total_debit, $journalEntry->total_credit);
        $this->assertEquals(5000.00, $journalEntry->total_debit);
    }

    /**
     * Test exit movement creates GL journal entry
     * DR: COGS / CR: Inventory Asset
     */
    public function test_exit_movement_creates_gl_journal_entry(): void
    {
        // Arrange: Create required GL accounts
        $this->createRequiredGLAccounts();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $initialCount = JournalEntry::count();

        // Act: Create exit movement
        $movement = InventoryMovement::create([
            'movement_type' => 'exit',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 2500.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'sale',
            // IV-009: Quality check required for exits
            'quality_checked' => true,
            'quality_checked_at' => now(),
            'quality_checked_by' => $user->id,
        ]);

        // Assert
        $this->assertEquals($initialCount + 1, JournalEntry::count());

        $movement->refresh();
        $this->assertNotNull($movement->gl_journal_entry_id);
        $this->assertEquals('posted', $movement->gl_posting_status);
    }

    /**
     * Test adjustment movement creates GL journal entry
     */
    public function test_adjustment_movement_creates_gl_journal_entry(): void
    {
        // Arrange: Create required GL accounts
        $this->createRequiredGLAccounts();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $initialCount = JournalEntry::count();

        // Act: Create adjustment movement (positive)
        $movement = InventoryMovement::create([
            'movement_type' => 'adjustment',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 500.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'adjustment',
        ]);

        // Assert
        $this->assertEquals($initialCount + 1, JournalEntry::count());

        $movement->refresh();
        $this->assertNotNull($movement->gl_journal_entry_id);
        $this->assertEquals('posted', $movement->gl_posting_status);
    }

    /**
     * Test transfer movement does not create GL journal entry
     * Internal transfers within same GL account don't need separate GL posting
     */
    public function test_transfer_movement_does_not_create_gl_journal_entry(): void
    {
        // Arrange: Create required GL accounts (transfers still need accounts setup)
        $this->createRequiredGLAccounts();

        $product = Product::factory()->create();
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $user = User::factory()->create();

        $initialCount = JournalEntry::count();

        // Act: Create transfer movement
        $movement = InventoryMovement::create([
            'movement_type' => 'transfer',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse1->id,
            'destination_warehouse_id' => $warehouse2->id,
            'quantity' => 25.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 1250.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'transfer',
            // IV-009: Quality check required for transfers
            'quality_checked' => true,
            'quality_checked_at' => now(),
            'quality_checked_by' => $user->id,
        ]);

        // Assert: No journal entry created for internal transfer
        $this->assertEquals($initialCount, JournalEntry::count());

        $movement->refresh();
        $this->assertNull($movement->gl_journal_entry_id);
        // Transfers are marked as 'posted' (internal transfer - no GL posting required)
        $this->assertEquals('posted', $movement->gl_posting_status);
    }

    /**
     * Test movement with existing GL entry is not posted again
     */
    public function test_movement_with_existing_gl_entry_is_not_posted_again(): void
    {
        // Arrange: Create required GL accounts
        $this->createRequiredGLAccounts();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        $initialCount = JournalEntry::count();

        // Create movement (should create GL entry)
        $movement = InventoryMovement::create([
            'movement_type' => 'entry',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 5000.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'purchase',
        ]);

        $this->assertEquals($initialCount + 1, JournalEntry::count());

        // Act: Try to trigger event again manually
        event(new \Modules\Inventory\Events\InventoryMovementCreated($movement));

        // Assert: Still only 1 new journal entry (not 2)
        $this->assertEquals($initialCount + 1, JournalEntry::count());
    }

    /**
     * Test GL posting failure is logged but does not fail movement creation
     */
    public function test_gl_posting_failure_is_handled_gracefully(): void
    {
        // This test intentionally does NOT create GL accounts
        // to verify that movement creation still succeeds even when GL posting fails

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        // Create movement (may fail to post to GL if accounts don't exist)
        $movement = InventoryMovement::create([
            'movement_type' => 'entry',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'unit_cost' => 50.00,
            'cost_per_unit' => 50.00,
            'total_cost' => 5000.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'purchase',
        ]);

        // Movement should exist even if GL posting failed
        $this->assertNotNull($movement->id);
        $movement->refresh();

        // Check posting status (could be 'posted', 'error', or null depending on account setup)
        // assertContains expects: needle, haystack
        $validStatuses = ['posted', 'error', null];
        $this->assertTrue(
            in_array($movement->gl_posting_status, $validStatuses),
            "Expected gl_posting_status to be one of: posted, error, null. Got: {$movement->gl_posting_status}"
        );
    }
}
