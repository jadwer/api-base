<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\FolioSequence;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Product\Models\Brand;

class QuoteFromCartWithETATest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Contact $contact;
    protected ShoppingCart $cart;
    protected Brand $brand;
    protected Product $productWithETA;
    protected Product $productWithoutETA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact = Contact::factory()->create();

        // Create brand with default lead time
        $this->brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '2-3 semanas',
        ]);

        // Create brand without lead time
        $brandNoEta = Brand::factory()->create([
            'name' => 'No ETA Brand',
            'default_lead_time' => null,
        ]);

        // Create products (both with IVA for predictable tax calculation)
        $this->productWithETA = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'name' => 'Product With ETA',
            'sku' => 'WITH-ETA-001',
            'price' => 100.00,
            'iva' => true,
        ]);

        $this->productWithoutETA = Product::factory()->create([
            'brand_id' => $brandNoEta->id,
            'name' => 'Product Without ETA',
            'sku' => 'NO-ETA-001',
            'price' => 200.00,
            'iva' => true,
        ]);

        // Create shopping cart using seeded admin
        $admin = $this->getAdminUser();
        $this->cart = ShoppingCart::factory()->create([
            'user_id' => $admin->id,
            'currency' => 'MXN',
        ]);

        // Add items to cart (bypass observer to preserve exact prices)
        CartItem::withoutEvents(function () {
            CartItem::factory()->create([
                'shopping_cart_id' => $this->cart->id,
                'product_id' => $this->productWithETA->id,
                'quantity' => 2,
                'unit_price' => 100.00,
                'subtotal' => 200.00,
                'tax_amount' => 32.00,
                'total' => 232.00,
            ]);

            CartItem::factory()->create([
                'shopping_cart_id' => $this->cart->id,
                'product_id' => $this->productWithoutETA->id,
                'quantity' => 1,
                'unit_price' => 200.00,
                'subtotal' => 200.00,
                'tax_amount' => 32.00,
                'total' => 232.00,
            ]);
        });

        // Use existing folio sequence from migration or create if not exists
        FolioSequence::updateOrCreate(
            ['document_type' => 'quote'],
            [
                'prefix' => 'COT',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'is_active' => true,
            ]
        );
    }

    /** @test */
    public function it_creates_quote_from_cart_with_eta_from_brand(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();
        $this->assertNotNull($quote);

        // Check quote items
        $items = $quote->items;
        $this->assertCount(2, $items);

        // Product with brand that has default_lead_time should have ETA
        $itemWithETA = $items->where('product_sku', 'WITH-ETA-001')->first();
        $this->assertEquals('ETA: 2-3 semanas', $itemWithETA->notes);

        // Product with brand without default_lead_time should NOT have ETA
        $itemWithoutETA = $items->where('product_sku', 'NO-ETA-001')->first();
        $this->assertNull($itemWithoutETA->notes);
    }

    /** @test */
    public function it_generates_configurable_folio_for_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();
        $year = now()->format('y');

        $this->assertEquals("COT{$year}000001", $quote->quote_number);
    }

    /** @test */
    public function it_generates_sequential_folios_for_multiple_quotes(): void
    {
        $admin = $this->getAdminUser();

        // Create first quote
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        // Create another cart for second quote
        $cart2 = ShoppingCart::factory()->create([
            'user_id' => $admin->id,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart2->id,
            'product_id' => $this->productWithETA->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        // Create second quote
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart2->id,
                'contact_id' => $this->contact->id,
            ]);

        $quotes = Quote::orderBy('id')->get();
        $year = now()->format('y');

        $this->assertEquals("COT{$year}000001", $quotes[0]->quote_number);
        $this->assertEquals("COT{$year}000002", $quotes[1]->quote_number);
    }

    /** @test */
    public function it_copies_product_info_to_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();
        $item = $quote->items->where('product_sku', 'WITH-ETA-001')->first();

        $this->assertEquals('Product With ETA', $item->product_name);
        $this->assertEquals('WITH-ETA-001', $item->product_sku);
        $this->assertEquals(100.00, $item->unit_price);
        $this->assertEquals(100.00, $item->quoted_price);
        $this->assertEquals(2, $item->quantity);
    }

    /** @test */
    public function it_calculates_quote_totals_correctly(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();

        // 2 * 100 + 1 * 200 = 400 subtotal
        // 400 * 0.16 = 64 tax
        // 400 + 64 = 464 total
        $this->assertEquals(400.00, $quote->subtotal_amount);
        $this->assertEquals(64.00, $quote->tax_amount);
        $this->assertEquals(464.00, $quote->total_amount);
    }
}
