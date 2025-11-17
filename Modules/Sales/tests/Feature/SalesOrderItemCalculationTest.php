<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Product\Models\Product;
use Modules\Contacts\Models\Contact;

class SalesOrderItemCalculationTest extends TestCase
{
    /**
     * Test that total is automatically calculated on create
     */
    public function test_total_automatically_calculated_on_create(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Act: Create item without specifying total
        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 25.50,
            'discount' => 5.00,
            // total is NOT specified - should be auto-calculated
        ]);

        // Assert: Total should be calculated as (quantity * unit_price) - discount
        // 10 * 25.50 - 5.00 = 255.00 - 5.00 = 250.00
        $this->assertEquals(250.00, $item->total);
        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'quantity' => 10.0000,
            'unit_price' => 25.50,
            'discount' => 5.00,
            'total' => 250.00,
        ]);
    }

    /**
     * Test that total is recalculated on update
     */
    public function test_total_recalculated_on_update(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100.00,
            'discount' => 0,
        ]);

        $this->assertEquals(500.00, $item->total);

        // Act: Update quantity
        $item->update(['quantity' => 10]);

        // Assert: Total should be recalculated
        // 10 * 100.00 - 0 = 1000.00
        $this->assertEquals(1000.00, $item->total);
    }

    /**
     * Test calculation with discount
     */
    public function test_calculation_with_discount(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Act: Create item with discount
        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_price' => 50.00,
            'discount' => 100.00,
        ]);

        // Assert: Total = (20 * 50.00) - 100.00 = 1000.00 - 100.00 = 900.00
        $this->assertEquals(900.00, $item->total);
    }

    /**
     * Test calculation without discount (defaults to 0)
     */
    public function test_calculation_without_discount(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Act: Create item without discount field
        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 75.00,
            // discount not specified
        ]);

        // Assert: Total = (3 * 75.00) - 0 = 225.00
        $this->assertEquals(225.00, $item->total);
    }

    /**
     * Test that manually set total is overwritten by auto-calculation
     */
    public function test_manually_set_total_is_overwritten(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Act: Try to create item with manually set (wrong) total
        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 25.00,
            'discount' => 0,
            'total' => 9999.99, // Wrong total
        ]);

        // Assert: Total should be auto-calculated correctly, ignoring manual value
        // 4 * 25.00 - 0 = 100.00
        $this->assertEquals(100.00, $item->total);
        $this->assertNotEquals(9999.99, $item->total);
    }

    /**
     * Test calculation with decimal quantities
     */
    public function test_calculation_with_decimal_quantities(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Act: Create item with decimal quantity
        $item = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 2.5,
            'unit_price' => 40.00,
            'discount' => 10.00,
        ]);

        // Assert: Total = (2.5 * 40.00) - 10.00 = 100.00 - 10.00 = 90.00
        $this->assertEquals(90.00, $item->total);
    }
}
