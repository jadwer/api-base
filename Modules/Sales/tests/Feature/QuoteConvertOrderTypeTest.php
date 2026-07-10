<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;

/**
 * Fase A - Venta directa vs Pedido.
 *
 * Convert con order_type:
 * - direct_sale: valida stock de todos los items (422 con detalle si falta), nace confirmed.
 * - order: requiere customer_po_number, no bloquea por stock (reporta items_requiring_purchase), nace pending.
 * - defaults de payment_method/credit_days: request > quote > contact.payment_terms ?? 30 y PPD/PUE.
 */
class QuoteConvertOrderTypeTest extends TestCase
{
    /**
     * Create an accepted quote with a single item.
     *
     * @return array{0: Quote, 1: Product, 2: Contact}
     */
    private function makeAcceptedQuote(array $quoteAttrs = [], array $contactAttrs = [], float $quantity = 5): array
    {
        $contact = Contact::factory()->customer()->create($contactAttrs);
        $quote = Quote::factory()->create(array_merge([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ], $quoteAttrs));

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'quoted_price' => 100,
        ]);

        return [$quote, $product, $contact];
    }

    private function giveStock(Product $product, float $quantity): void
    {
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'warehouse_location_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);
    }

    // ==================== direct_sale ====================

    public function test_direct_sale_with_sufficient_stock_creates_confirmed_order(): void
    {
        $admin = $this->getAdminUser();
        [$quote, $product] = $this->makeAcceptedQuote(quantity: 5);
        $this->giveStock($product, 10);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'direct_sale',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('confirmed', $response->json('data.salesOrder.attributes.status'));
        $this->assertEquals('direct_sale', $response->json('data.salesOrder.attributes.orderType'));

        $this->assertDatabaseHas('sales_orders', [
            'id' => $response->json('data.salesOrder.id'),
            'order_type' => 'direct_sale',
            'status' => 'confirmed',
        ]);
    }

    public function test_direct_sale_without_stock_is_blocked_with_item_detail(): void
    {
        $admin = $this->getAdminUser();
        [$quote, $product] = $this->makeAcceptedQuote(quantity: 5);
        $this->giveStock($product, 2);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'direct_sale',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                ['product_id', 'product_name', 'requested', 'available'],
            ],
        ]);
        $this->assertEquals($product->id, $response->json('errors.0.product_id'));
        $this->assertEquals(5.0, $response->json('errors.0.requested'));
        $this->assertEquals(2.0, $response->json('errors.0.available'));

        // No order was created and the quote is still convertible
        $this->assertDatabaseMissing('sales_orders', ['quote_id' => $quote->id]);
        $this->assertEquals('accepted', $quote->fresh()->status);
    }

    public function test_direct_sale_does_not_require_customer_po_number(): void
    {
        $admin = $this->getAdminUser();
        [$quote, $product] = $this->makeAcceptedQuote(quantity: 3);
        $this->giveStock($product, 3);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'direct_sale',
            ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.salesOrder.attributes.customerPoNumber'));
    }

    // ==================== order (pedido) ====================

    public function test_order_type_order_requires_customer_po_number(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_po_number']);
    }

    public function test_order_without_stock_is_not_blocked_and_reports_items_requiring_purchase(): void
    {
        $admin = $this->getAdminUser();
        [$quote, $product] = $this->makeAcceptedQuote(quantity: 5);
        // Sin stock: el pedido procede igual

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-CLIENTE-001',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('pending', $response->json('data.salesOrder.attributes.status'));
        $this->assertEquals('order', $response->json('data.salesOrder.attributes.orderType'));
        $this->assertEquals('OC-CLIENTE-001', $response->json('data.salesOrder.attributes.customerPoNumber'));

        $itemsRequiringPurchase = $response->json('data.items_requiring_purchase');
        $this->assertCount(1, $itemsRequiringPurchase);
        $this->assertEquals($product->id, $itemsRequiringPurchase[0]['product_id']);
        $this->assertEquals(5.0, $itemsRequiringPurchase[0]['requested']);

        $this->assertDatabaseHas('sales_orders', [
            'quote_id' => $quote->id,
            'order_type' => 'order',
            'customer_po_number' => 'OC-CLIENTE-001',
        ]);
    }

    public function test_order_with_full_stock_reports_no_items_requiring_purchase(): void
    {
        $admin = $this->getAdminUser();
        [$quote, $product] = $this->makeAcceptedQuote(quantity: 4);
        $this->giveStock($product, 20);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-CLIENTE-002',
            ]);

        $response->assertStatus(201);
        $this->assertSame([], $response->json('data.items_requiring_purchase'));
    }

    public function test_convert_without_order_type_defaults_to_order_for_backward_compatibility(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);
        $this->assertEquals('order', $response->json('data.salesOrder.attributes.orderType'));
        $this->assertEquals('pending', $response->json('data.salesOrder.attributes.status'));
    }

    // ==================== defaults payment_method / credit_days ====================

    public function test_payment_fields_default_from_quote(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote([
            'payment_method' => 'PUE',
            'credit_days' => 15,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-100',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('PUE', $response->json('data.salesOrder.attributes.paymentMethod'));
        $this->assertEquals(15, $response->json('data.salesOrder.attributes.creditDays'));
    }

    public function test_credit_days_fall_back_to_contact_payment_terms_and_payment_method_to_ppd(): void
    {
        $admin = $this->getAdminUser();
        // Quote sin payment_method ni credit_days; contacto con payment_terms=45
        [$quote] = $this->makeAcceptedQuote(contactAttrs: ['payment_terms' => 45]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-101',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(45, $response->json('data.salesOrder.attributes.creditDays'));
        // credit_days > 0 => PPD
        $this->assertEquals('PPD', $response->json('data.salesOrder.attributes.paymentMethod'));
    }

    public function test_zero_credit_days_defaults_payment_method_to_pue(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-102',
                'credit_days' => 0,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(0, $response->json('data.salesOrder.attributes.creditDays'));
        $this->assertEquals('PUE', $response->json('data.salesOrder.attributes.paymentMethod'));
    }

    public function test_request_payment_fields_override_quote_values(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote([
            'payment_method' => 'PPD',
            'credit_days' => 30,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-103',
                'payment_method' => 'PUE',
                'credit_days' => 7,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('PUE', $response->json('data.salesOrder.attributes.paymentMethod'));
        $this->assertEquals(7, $response->json('data.salesOrder.attributes.creditDays'));
    }

    public function test_invalid_order_type_is_rejected(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'invalid',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_type']);
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $admin = $this->getAdminUser();
        [$quote] = $this->makeAcceptedQuote();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'customer_po_number' => 'OC-104',
                'payment_method' => 'ABC',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
    }
}
