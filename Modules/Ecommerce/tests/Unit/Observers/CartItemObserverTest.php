<?php

namespace Modules\Ecommerce\Tests\Unit\Observers;

use App\Services\ConversionResult;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\AppConfig\Models\AppSetting;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Currency;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Observers\CartItemObserver;
use Modules\Product\Models\Product;
use Tests\TestCase;

class CartItemObserverTest extends TestCase
{
    use RefreshDatabase;

    private Currency $mxn;
    private Currency $usd;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base currencies (use firstOrCreate to avoid duplicate if seeder ran)
        $this->mxn = Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Mexican Peso',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->mxn->update(['exchange_rate' => 1.0, 'is_active' => true]);

        $this->usd = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => 'US$',
            'exchange_rate' => 17.5,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->usd->update(['exchange_rate' => 17.5, 'is_active' => true]);

        // Set exchange rate margin to 0 by default
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '0',
            'type' => 'string',
            'group' => 'currency',
        ]);
    }

    /**
     * Helper to create a ShoppingCart with a specific currency string.
     */
    private function createCart(string $currency = 'MXN', ?int $currencyId = null): ShoppingCart
    {
        return ShoppingCart::factory()->create([
            'currency' => $currency,
            'currency_id' => $currencyId,
            'status' => 'active',
        ]);
    }

    /**
     * Helper to create a Product with a specific currency_id.
     */
    private function createProduct(int $currencyId, float $price = 100.00): Product
    {
        return Product::factory()->create([
            'currency_id' => $currencyId,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------
    // Same currency - no conversion
    // -------------------------------------------------------

    public function test_no_conversion_when_product_currency_matches_cart_currency(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->mxn->id, 100.00);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 200.00,
            'tax_rate' => 16,
            'tax_amount' => 32.00,
            'total' => 232.00,
        ]);

        $cartItem->refresh();

        // No conversion fields should be set
        $this->assertNull($cartItem->original_currency_code);
        $this->assertNull($cartItem->original_unit_price);
        $this->assertNull($cartItem->exchange_rate_used);

        // Original values preserved
        $this->assertEquals(100.00, $cartItem->unit_price);
        $this->assertEquals(200.00, $cartItem->subtotal);
        $this->assertEquals(32.00, $cartItem->tax_amount);
        $this->assertEquals(232.00, $cartItem->total);
    }

    public function test_no_conversion_when_both_currencies_are_usd(): void
    {
        $cart = $this->createCart('USD');
        $product = $this->createProduct($this->usd->id, 50.00);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'original_price' => 50.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 50.00,
            'tax_rate' => 16,
            'tax_amount' => 8.00,
            'total' => 58.00,
        ]);

        $cartItem->refresh();

        $this->assertNull($cartItem->original_currency_code);
        $this->assertNull($cartItem->original_unit_price);
        $this->assertNull($cartItem->exchange_rate_used);
        $this->assertEquals(50.00, $cartItem->unit_price);
    }

    // -------------------------------------------------------
    // Different currency - conversion applied
    // -------------------------------------------------------

    public function test_conversion_applied_when_product_currency_differs_from_cart(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        // Mock the CurrencyConversionService
        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1750.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        $cartItem->refresh();

        // Converted price should be applied
        $this->assertEquals(1750.00, $cartItem->unit_price);
        $this->assertEquals(1750.00, $cartItem->original_price);
    }

    // -------------------------------------------------------
    // Original currency fields stored
    // -------------------------------------------------------

    public function test_original_currency_fields_are_stored_on_conversion(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1750.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        $cartItem->refresh();

        $this->assertEquals('USD', $cartItem->original_currency_code);
        $this->assertEquals(100.00, $cartItem->original_unit_price);
        $this->assertEquals(17.5, $cartItem->exchange_rate_used);
    }

    public function test_original_currency_fields_persisted_in_database(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 250.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(250.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 250.00,
                convertedAmount: 4375.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 250.00,
            'original_price' => 250.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 250.00,
            'tax_rate' => 16,
            'tax_amount' => 40.00,
            'total' => 290.00,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'original_currency_code' => 'USD',
            'original_unit_price' => 250.00,
            'exchange_rate_used' => 17.5,
            'unit_price' => 4375.00,
        ]);
    }

    // -------------------------------------------------------
    // Subtotal, tax, total recalculated after conversion
    // -------------------------------------------------------

    public function test_subtotal_tax_total_recalculated_after_conversion(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1750.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 300.00,
            'tax_rate' => 16,
            'tax_amount' => 48.00,
            'total' => 348.00,
        ]);

        $cartItem->refresh();

        // subtotal = 1750 * 3 = 5250
        $this->assertEquals(5250.00, $cartItem->subtotal);
        // tax_amount = 5250 * 0.16 = 840
        $this->assertEquals(840.00, $cartItem->tax_amount);
        // total = 5250 + 840 = 6090
        $this->assertEquals(6090.00, $cartItem->total);
    }

    public function test_subtotal_recalculated_with_quantity_one(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 50.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(50.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 50.00,
                convertedAmount: 875.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'original_price' => 50.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 50.00,
            'tax_rate' => 16,
            'tax_amount' => 8.00,
            'total' => 58.00,
        ]);

        $cartItem->refresh();

        // subtotal = 875 * 1 = 875
        $this->assertEquals(875.00, $cartItem->subtotal);
        // tax = 875 * 0.16 = 140
        $this->assertEquals(140.00, $cartItem->tax_amount);
        // total = 875 + 140 = 1015
        $this->assertEquals(1015.00, $cartItem->total);
    }

    public function test_recalculation_with_zero_tax_rate(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 200.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(200.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 200.00,
                convertedAmount: 3500.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 200.00,
            'original_price' => 200.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 400.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 400.00,
        ]);

        $cartItem->refresh();

        // subtotal = 3500 * 2 = 7000
        $this->assertEquals(7000.00, $cartItem->subtotal);
        // tax = 7000 * 0 = 0
        $this->assertEquals(0.00, $cartItem->tax_amount);
        // total = 7000 + 0 = 7000
        $this->assertEquals(7000.00, $cartItem->total);
    }

    // -------------------------------------------------------
    // Conversion with margin
    // -------------------------------------------------------

    public function test_conversion_with_exchange_rate_margin(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        // Mock with a margin-applied effective rate
        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1837.50, // 17.5 * 1.05 = 18.375, 100 * 18.375 = 1837.50
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 5.0,
                effectiveRate: 18.375,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        $cartItem->refresh();

        $this->assertEquals(1837.50, $cartItem->unit_price);
        $this->assertEquals(18.375, $cartItem->exchange_rate_used);
        $this->assertEquals(100.00, $cartItem->original_unit_price);
        $this->assertEquals('USD', $cartItem->original_currency_code);
    }

    // -------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------

    public function test_observer_does_nothing_when_product_has_no_currency_relation(): void
    {
        $cart = $this->createCart('MXN');

        // Create a product without a currency_id (null)
        $product = Product::factory()->create([
            'currency_id' => null,
            'price' => 100.00,
            'is_active' => true,
        ]);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        $cartItem->refresh();

        // No conversion should have happened
        $this->assertNull($cartItem->original_currency_code);
        $this->assertNull($cartItem->original_unit_price);
        $this->assertNull($cartItem->exchange_rate_used);
        $this->assertEquals(100.00, $cartItem->unit_price);
    }

    public function test_observer_uses_default_quantity_of_one_when_quantity_null(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1750.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        // Create cart item explicitly invoking the observer by manually calling creating
        $cartItem = new CartItem([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => null,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        // Trigger observer manually
        $observer = new CartItemObserver();
        $observer->creating($cartItem);

        // When quantity is null, defaults to 1
        // subtotal = 1750 * 1 = 1750
        $this->assertEquals(1750.00, $cartItem->subtotal);
        $this->assertEquals(280.00, $cartItem->tax_amount); // 1750 * 0.16
        $this->assertEquals(2030.00, $cartItem->total); // 1750 + 280
    }

    public function test_observer_uses_default_tax_rate_of_16_when_tax_rate_null(): void
    {
        $cart = $this->createCart('MXN');
        $product = $this->createProduct($this->usd->id, 100.00);

        $mockService = Mockery::mock(CurrencyConversionService::class);
        $mockService->shouldReceive('convert')
            ->once()
            ->with(100.00, 'USD', 'MXN')
            ->andReturn(new ConversionResult(
                originalAmount: 100.00,
                convertedAmount: 1750.00,
                fromCurrency: 'USD',
                toCurrency: 'MXN',
                exchangeRate: 17.5,
                marginApplied: 0.0,
                effectiveRate: 17.5,
                rateDate: Carbon::now(),
            ));
        $this->app->instance(CurrencyConversionService::class, $mockService);

        $cartItem = new CartItem([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 200.00,
            'tax_rate' => null,
            'tax_amount' => 0,
            'total' => 200.00,
        ]);

        $observer = new CartItemObserver();
        $observer->creating($cartItem);

        // subtotal = 1750 * 2 = 3500
        $this->assertEquals(3500.00, $cartItem->subtotal);
        // tax_rate defaults to 16: 3500 * 0.16 = 560
        $this->assertEquals(560.00, $cartItem->tax_amount);
        $this->assertEquals(4060.00, $cartItem->total);
    }

    public function test_cart_with_mxn_currency_and_mxn_product_has_no_conversion(): void
    {
        // Create cart with explicit MXN (the DB default)
        $cart = $this->createCart('MXN', $this->mxn->id);
        $product = $this->createProduct($this->mxn->id, 100.00);

        $cartItem = CartItem::create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'original_price' => 100.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 100.00,
            'tax_rate' => 16,
            'tax_amount' => 16.00,
            'total' => 116.00,
        ]);

        $cartItem->refresh();

        // Same currency -> no conversion
        $this->assertNull($cartItem->original_currency_code);
        $this->assertEquals(100.00, $cartItem->unit_price);
    }
}
