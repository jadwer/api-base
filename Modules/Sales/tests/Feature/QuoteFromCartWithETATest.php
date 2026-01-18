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
use App\Models\User;

class QuoteFromCartWithETATest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected Contact $contact;
    protected ShoppingCart $cart;
    protected Brand $brand;
    protected Product $productInStock;
    protected Product $productOutOfStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->contact = Contact::factory()->create();

        // Create brand with default lead time
        $this->brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '2-3 semanas',
        ]);

        // Create products
        $this->productInStock = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'name' => 'Product In Stock',
            'sku' => 'IN-STOCK-001',
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $this->productOutOfStock = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'name' => 'Product Out of Stock',
            'sku' => 'OUT-STOCK-001',
            'price' => 200.00,
            'stock_quantity' => 0,
        ]);

        // Create shopping cart
        $this->cart = ShoppingCart::factory()->create([
            'user_id' => $this->admin->id,
            'currency' => 'MXN',
        ]);

        // Add items to cart
        CartItem::factory()->create([
            'shopping_cart_id' => $this->cart->id,
            'product_id' => $this->productInStock->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $this->cart->id,
            'product_id' => $this->productOutOfStock->id,
            'quantity' => 1,
            'unit_price' => 200.00,
        ]);

        // Create folio sequence
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'include_year' => true,
            'year_format' => 'y',
            'separator' => '',
            'padding' => 6,
            'current_sequence' => 0,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_quote_from_cart_with_eta_for_out_of_stock_items(): void
    {
        $response = $this->actingAs($this->admin)
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

        // In-stock item should not have ETA note
        $inStockItem = $items->where('product_sku', 'IN-STOCK-001')->first();
        $this->assertNull($inStockItem->notes);

        // Out-of-stock item should have ETA note from brand
        $outOfStockItem = $items->where('product_sku', 'OUT-STOCK-001')->first();
        $this->assertEquals('ETA: 2-3 semanas', $outOfStockItem->notes);
    }

    /** @test */
    public function it_does_not_add_eta_when_brand_has_no_default_lead_time(): void
    {
        // Create brand without lead time
        $brandNoEta = Brand::factory()->create([
            'name' => 'No ETA Brand',
            'default_lead_time' => null,
        ]);

        $productNoEta = Product::factory()->create([
            'brand_id' => $brandNoEta->id,
            'name' => 'No ETA Product',
            'sku' => 'NO-ETA-001',
            'price' => 150.00,
            'stock_quantity' => 0,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $this->cart->id,
            'product_id' => $productNoEta->id,
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();
        $noEtaItem = $quote->items->where('product_sku', 'NO-ETA-001')->first();

        $this->assertNull($noEtaItem->notes);
    }

    /** @test */
    public function it_generates_configurable_folio_for_quote(): void
    {
        $response = $this->actingAs($this->admin)
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
        // Create first quote
        $this->actingAs($this->admin)
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        // Create another cart for second quote
        $cart2 = ShoppingCart::factory()->create([
            'user_id' => $this->admin->id,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart2->id,
            'product_id' => $this->productInStock->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        // Create second quote
        $this->actingAs($this->admin)
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
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $this->cart->id,
                'contact_id' => $this->contact->id,
            ]);

        $response->assertStatus(201);

        $quote = Quote::first();
        $item = $quote->items->where('product_sku', 'IN-STOCK-001')->first();

        $this->assertEquals('Product In Stock', $item->product_name);
        $this->assertEquals('IN-STOCK-001', $item->product_sku);
        $this->assertEquals(100.00, $item->unit_price);
        $this->assertEquals(100.00, $item->quoted_price);
        $this->assertEquals(2, $item->quantity);
    }

    /** @test */
    public function it_calculates_quote_totals_correctly(): void
    {
        $response = $this->actingAs($this->admin)
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
