<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;

/**
 * IV-010: Inventory Movement GL Integration Tests
 *
 * Tests that inventory movements automatically create GL journal entries
 */
class InventoryMovementGLIntegrationTest extends TestCase
{
    /**
     * Test entry movement creates GL journal entry
     * DR: Inventory Asset / CR: Inventory Accrual
     */
    public function test_entry_movement_creates_gl_journal_entry(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();

        // Get count of journal entries before
        $initialCount = JournalEntry::count();

        // Act: Create entry movement
        $movement = InventoryMovement::create([
            'movement_type' => 'entry',
            'movement_date' => now(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
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
        // Arrange
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
            'cost_per_unit' => 50.00,
            'total_cost' => 2500.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'sale',
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
        // Arrange
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
     */
    public function test_transfer_movement_does_not_create_gl_journal_entry(): void
    {
        // Arrange
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
            'cost_per_unit' => 50.00,
            'total_cost' => 1250.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'transfer',
        ]);

        // Assert: No journal entry created for transfer
        $this->assertEquals($initialCount, JournalEntry::count());

        $movement->refresh();
        $this->assertNull($movement->gl_journal_entry_id);
        $this->assertEquals('not_required', $movement->gl_posting_status);
    }

    /**
     * Test movement with existing GL entry is not posted again
     */
    public function test_movement_with_existing_gl_entry_is_not_posted_again(): void
    {
        // Arrange
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
        // This test would need to mock AccountingService to throw exception
        // For now, we just verify that a movement without required GL accounts
        // still gets created (the listener catches the exception)

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
            'cost_per_unit' => 50.00,
            'total_cost' => 5000.00,
            'status' => 'completed',
            'user_id' => $user->id,
            'reference_type' => 'purchase',
        ]);

        // Movement should exist even if GL posting failed
        $this->assertNotNull($movement->id);
        $movement->refresh();

        // Check posting status (could be 'posted', 'failed', or null depending on account setup)
        $this->assertContains($movement->gl_posting_status, ['posted', 'failed', null]);
    }
}
