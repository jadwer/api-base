<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Contacts\Models\Contact;

class PurchaseOrderInventoryIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up seeded inventory movements with reference_type=purchase
        // to avoid collisions with random reference_ids from InventoryMovementSeeder
        InventoryMovement::where('reference_type', 'purchase')->delete();
    }

    /**
     * Test that inventory movements are created when purchase order is marked as received
     */
    public function test_inventory_movements_created_when_purchase_order_received(): void
    {
        // Arrange: Create necessary data
        $admin = $this->getAdminUser();
        // Create a specific warehouse to ensure deterministic behavior
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $supplier = Contact::factory()->create(['is_supplier' => true]);

        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        // Create a purchase order with items, explicitly setting the warehouse
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'warehouse_id' => $warehouse->id, // Explicitly set warehouse
            'status' => 'approved', // Start as approved, not received yet
            'total_amount' => 1000.00,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product2->id,
            'quantity' => 5,
            'unit_price' => 100.00,
        ]);

        // Verify no inventory movements exist yet
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => 'purchase',
            'reference_id' => $purchaseOrder->id,
        ]);

        // Act: Mark purchase order as received (triggers observer -> event -> listener)
        $purchaseOrder->update(['status' => 'received']);

        // Assert: Verify inventory movements were created (core fields only)
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'entry',
            'reference_type' => 'purchase',
            'reference_id' => $purchaseOrder->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'entry',
            'reference_type' => 'purchase',
            'reference_id' => $purchaseOrder->id,
            'status' => 'completed',
        ]);

        // Verify movement quantities separately (DB stores as string decimals)
        $movement1 = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->where('product_id', $product1->id)
            ->first();
        $this->assertEquals(10, (float) $movement1->quantity);
        $this->assertEquals(50, (float) $movement1->unit_cost);

        $movement2 = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->where('product_id', $product2->id)
            ->first();
        $this->assertEquals(5, (float) $movement2->quantity);
        $this->assertEquals(100, (float) $movement2->unit_cost);

        // Verify stock was created/updated for these products
        $this->assertDatabaseHas('stock', [
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->assertDatabaseHas('stock', [
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
        ]);

        // Verify exactly 2 movements were created
        $movements = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->get();

        $this->assertCount(2, $movements);
    }

    /**
     * Test that inventory movements are NOT created when purchase order status changes to non-received status
     */
    public function test_inventory_movements_not_created_for_other_status_changes(): void
    {
        // Arrange
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'pending',
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        // Act: Change status to 'approved' (not 'received')
        $purchaseOrder->update(['status' => 'approved']);

        // Assert: No inventory movements should be created
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => 'purchase',
            'reference_id' => $purchaseOrder->id,
        ]);
    }

    /**
     * Test that inventory movements include proper metadata
     */
    public function test_inventory_movements_include_metadata(): void
    {
        // Arrange
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        // Act
        $purchaseOrder->update(['status' => 'received']);

        // Assert: Verify metadata contains purchase order information
        $movement = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertArrayHasKey('purchase_order_id', $movement->metadata);
        $this->assertArrayHasKey('purchase_order_item_id', $movement->metadata);
        $this->assertArrayHasKey('supplier_id', $movement->metadata);
        $this->assertEquals($purchaseOrder->id, $movement->metadata['purchase_order_id']);
        $this->assertEquals($item->id, $movement->metadata['purchase_order_item_id']);
        $this->assertEquals($supplier->id, $movement->metadata['supplier_id']);
    }

    /**
     * Test that updating to received status twice doesn't duplicate inventory movements
     */
    public function test_receiving_twice_creates_duplicate_movements(): void
    {
        // Arrange
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'approved',
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        // Act: Receive the order
        $purchaseOrder->update(['status' => 'received']);

        // Count movements after first receive
        $movementsAfterFirst = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->count();

        // Update to received again (simulating re-saving)
        $purchaseOrder->update(['status' => 'received']);

        // Count movements after second "receive"
        $movementsAfterSecond = InventoryMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchaseOrder->id)
            ->count();

        // Assert: Should still have only 1 movement (observer uses isDirty check)
        $this->assertEquals(1, $movementsAfterFirst);
        $this->assertEquals(1, $movementsAfterSecond);
    }
}
