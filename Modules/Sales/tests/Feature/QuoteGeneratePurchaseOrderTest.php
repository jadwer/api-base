<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Purchase\Models\PurchaseOrder;

class QuoteGeneratePurchaseOrderTest extends TestCase
{
    public function test_admin_can_generate_purchase_order_from_accepted_quote(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();
        $warehouse = Warehouse::factory()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'subtotal_amount' => 200,
            'tax_amount' => 32,
            'total_amount' => 232,
        ]);

        $product = Product::factory()->create(['cost' => 80]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'quoted_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Purchase order generated successfully from quote',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'quote',
                'purchaseOrder' => [
                    'type',
                    'id',
                    'attributes' => [
                        'orderNumber',
                        'status',
                        'totalAmount',
                        'supplierId',
                        'warehouseId',
                    ],
                ],
            ],
        ]);

        // Check quote is linked to PO
        $quote->refresh();
        $this->assertNotNull($quote->purchase_order_id);

        // PO should use product cost, not quoted price
        $poId = $response->json('data.purchaseOrder.id');
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $poId,
            'product_id' => $product->id,
            'unit_price' => 80, // Product cost
        ]);
    }

    public function test_can_generate_purchase_order_from_converted_quote(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_cannot_generate_purchase_order_from_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Cannot generate purchase order. Quote must be accepted or converted and not already have a PO.',
        ]);
    }

    public function test_cannot_generate_purchase_order_from_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(400);
    }

    public function test_cannot_generate_duplicate_purchase_order(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        // Create an existing PurchaseOrder first
        $existingPO = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
            'purchase_order_id' => $existingPO->id, // Already has a PO
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Cannot generate purchase order. Quote must be accepted or converted and not already have a PO.',
        ]);
    }

    public function test_requires_supplier_id(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_id']);
    }

    public function test_validates_supplier_exists(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_id']);
    }

    public function test_can_specify_warehouse(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();
        $warehouse = Warehouse::factory()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(201);
        $this->assertEquals($warehouse->id, $response->json('data.purchaseOrder.attributes.warehouseId'));
    }

    public function test_can_add_notes_to_purchase_order(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
                'notes' => 'Urgent order - rush delivery',
            ]);

        $response->assertStatus(201);

        $poId = $response->json('data.purchaseOrder.id');
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $poId,
            'notes' => 'Urgent order - rush delivery',
        ]);
    }

    public function test_guest_cannot_generate_purchase_order(): void
    {
        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
            'supplier_id' => $supplier->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_uses_unit_price_when_product_has_no_cost(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create(['cost' => null]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 150,
            'quoted_price' => 150,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(201);

        $poId = $response->json('data.purchaseOrder.id');
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $poId,
            'product_id' => $product->id,
            'unit_price' => 150, // Fallback to unit_price
        ]);
    }

    public function test_generates_unique_order_number(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote1 = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $quote2 = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote1->id,
            'product_id' => $product->id,
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote2->id,
            'product_id' => $product->id,
        ]);

        $response1 = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote1->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response2 = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote2->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $orderNumber1 = $response1->json('data.purchaseOrder.attributes.orderNumber');
        $orderNumber2 = $response2->json('data.purchaseOrder.attributes.orderNumber');

        $this->assertNotEquals($orderNumber1, $orderNumber2);
    }

    public function test_purchase_order_includes_all_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $customer = Contact::factory()->customer()->create();
        $supplier = Contact::factory()->supplier()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product3->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/generate-purchase-order", [
                'supplier_id' => $supplier->id,
            ]);

        $response->assertStatus(201);

        $poId = $response->json('data.purchaseOrder.id');

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $poId,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $poId,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $poId,
            'product_id' => $product3->id,
            'quantity' => 10,
        ]);
    }
}
