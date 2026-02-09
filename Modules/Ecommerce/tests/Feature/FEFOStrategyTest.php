<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Services\InventoryReservationService;
use Modules\Inventory\Models\ProductBatch;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\User\Models\User;
use Carbon\Carbon;

class FEFOStrategyTest extends TestCase
{
    private InventoryReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryReservationService::class);
    }

    /**
     * Test FEFO selects batch with earliest expiration date
     */
    public function test_fefo_selects_earliest_expiring_batch(): void
    {
        // Arrange: Create product and warehouse
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Create 3 batches with different expiration dates
        $batchLate = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-LATE',
            'expiration_date' => now()->addDays(90),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $batchEarly = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-EARLY',
            'expiration_date' => now()->addDays(10), // Expires soonest
            'current_quantity' => 50.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $batchMid = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-MID',
            'expiration_date' => now()->addDays(45),
            'current_quantity' => 75.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Act: Select batch using FEFO
        $selectedBatch = $this->service->selectBatchFEFO($product->id, 20.0);

        // Assert: Should select the earliest expiring batch
        $this->assertNotNull($selectedBatch);
        $this->assertEquals('BATCH-EARLY', $selectedBatch->batch_number);
        $this->assertEquals($batchEarly->id, $selectedBatch->id);
    }

    /**
     * Test FEFO skips batch with insufficient quantity
     */
    public function test_fefo_skips_batch_with_insufficient_quantity(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Early batch with insufficient quantity
        $batchEarly = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-EARLY-SMALL',
            'expiration_date' => now()->addDays(5),
            'current_quantity' => 10.0, // Not enough
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Later batch with sufficient quantity
        $batchLate = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-LATE-LARGE',
            'expiration_date' => now()->addDays(60),
            'current_quantity' => 100.0, // Enough
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Act: Request 50 units
        $selectedBatch = $this->service->selectBatchFEFO($product->id, 50.0);

        // Assert: Should skip early batch and select later batch with enough quantity
        $this->assertNotNull($selectedBatch);
        $this->assertEquals('BATCH-LATE-LARGE', $selectedBatch->batch_number);
    }

    /**
     * Test FEFO considers available_quantity (current - reserved)
     */
    public function test_fefo_considers_reserved_quantity(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $batch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-RESERVED',
            'expiration_date' => now()->addDays(30),
            'current_quantity' => 100.0,
            'reserved_quantity' => 80.0, // Most is reserved
            'status' => 'active',
        ]);

        // Act: Request 30 units (more than available_quantity = 20)
        $selectedBatch = $this->service->selectBatchFEFO($product->id, 30.0);

        // Assert: Should return null because available_quantity = 100 - 80 = 20 < 30
        $this->assertNull($selectedBatch);
    }

    /**
     * Test FEFO only selects active batches
     */
    public function test_fefo_only_selects_active_batches(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Expired batch (earliest expiration, but not active)
        $batchExpired = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-EXPIRED',
            'expiration_date' => now()->addDays(5),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'expired',
        ]);

        // Active batch (later expiration)
        $batchActive = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-ACTIVE',
            'expiration_date' => now()->addDays(60),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Act
        $selectedBatch = $this->service->selectBatchFEFO($product->id, 50.0);

        // Assert: Should select active batch, not expired one
        $this->assertNotNull($selectedBatch);
        $this->assertEquals('BATCH-ACTIVE', $selectedBatch->batch_number);
    }

    /**
     * Test FEFO puts batches without expiration date last
     */
    public function test_fefo_prioritizes_batches_with_expiration_date(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Batch without expiration date
        $batchNoExpiry = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-NO-EXPIRY',
            'expiration_date' => null, // No expiration
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Batch with expiration date
        $batchWithExpiry = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-WITH-EXPIRY',
            'expiration_date' => now()->addDays(180), // Far future
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        // Act
        $selectedBatch = $this->service->selectBatchFEFO($product->id, 50.0);

        // Assert: Should select batch with expiration date, not the one without
        $this->assertNotNull($selectedBatch);
        $this->assertEquals('BATCH-WITH-EXPIRY', $selectedBatch->batch_number);
    }

    /**
     * Test integration: Reservation from batch updates quantities
     */
    public function test_reservation_from_batch_updates_quantities(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $batch = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-TEST',
            'expiration_date' => now()->addDays(30),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $cart = ShoppingCart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 25.0,
            'unit_price' => 10.00,
            'original_price' => 10.00,
        ]);

        $session = CheckoutSession::factory()->create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $user->id,
            'status' => 'initiated',
        ]);

        // Act: Reserve inventory
        $result = $this->service->reserveItems($session);

        // Assert: Check reservation was created
        $this->assertEquals('success', $result['status']);
        $this->assertCount(1, $result['reservations']);

        // Assert: Check batch quantities were updated
        // Reservation only increments reserved_quantity; current_quantity stays unchanged
        // available_quantity is a stored generated column: current_quantity - reserved_quantity
        $batch->refresh();
        $this->assertEquals(100.0, $batch->current_quantity); // Unchanged during reservation
        $this->assertEquals(25.0, $batch->reserved_quantity);
        $this->assertEquals(75.0, $batch->available_quantity); // Generated: 100 - 25

        // Assert: Check reservation notes contain batch info
        $reservation = $result['reservations'][0];
        $this->assertStringContainsString('BATCH-TEST', $reservation->notes);
        $this->assertStringContainsString('FEFO', $reservation->notes);
    }

    /**
     * Test integration: Multiple batches - selects earliest first
     */
    public function test_checkout_with_multiple_batches_uses_fefo(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Create batches in non-chronological order
        $batch3 = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-2025-12',
            'expiration_date' => Carbon::parse('2025-12-31'),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $batch1 = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-2025-03',
            'expiration_date' => Carbon::parse('2025-03-15'), // Earliest
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $batch2 = ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-2025-06',
            'expiration_date' => Carbon::parse('2025-06-30'),
            'current_quantity' => 100.0,
            'reserved_quantity' => 0.0,
            'status' => 'active',
        ]);

        $cart = ShoppingCart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 30.0,
            'unit_price' => 15.00,
            'original_price' => 15.00,
        ]);

        $session = CheckoutSession::factory()->create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $user->id,
            'status' => 'initiated',
        ]);

        // Act
        $result = $this->service->reserveItems($session);

        // Assert: Should use earliest expiring batch (BATCH-2025-03)
        $this->assertEquals('success', $result['status']);
        $reservation = $result['reservations'][0];
        $this->assertStringContainsString('BATCH-2025-03', $reservation->notes);

        // Verify batch was updated (reservation only increments reserved_quantity)
        $batch1->refresh();
        $this->assertEquals(100.0, $batch1->current_quantity); // Unchanged during reservation
        $this->assertEquals(30.0, $batch1->reserved_quantity);

        // Other batches should be unchanged
        $batch2->refresh();
        $batch3->refresh();
        $this->assertEquals(100.0, $batch2->current_quantity);
        $this->assertEquals(100.0, $batch3->current_quantity);
    }
}
