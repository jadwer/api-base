<?php

namespace Tests\Feature\Security;

use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * El cliente de la tienda mandaba el precio, el descuento, el IVA y el total de cada
 * partida de su carrito y el checkout los copiaba al pedido. Ahora el servidor los toma
 * del catalogo: el cliente solo elige producto y cantidad.
 */
class CarritoPrecioServidor20261122Test extends TestCase
{
    private function cartFor($user): ShoppingCart
    {
        return ShoppingCart::factory()->create(['user_id' => $user->id, 'status' => 'active', 'currency' => 'MXN']);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 1000,
            'iva' => true,
            'tax_rate' => 16,
            'currency_id' => null,
        ], $attributes));
    }

    private function payload(ShoppingCart $cart, Product $product, array $attributes): array
    {
        return [
            'type' => 'cart-items',
            'attributes' => $attributes,
            'relationships' => [
                'shoppingCart' => ['data' => ['type' => 'shopping-carts', 'id' => (string) $cart->id]],
                'product' => ['data' => ['type' => 'products', 'id' => (string) $product->id]],
            ],
        ];
    }

    public function test_customer_cannot_set_the_price_when_adding_to_the_cart(): void
    {
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer);
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cart-items')
            ->withData($this->payload($cart, $product, [
                'quantity' => 2,
                'unitPrice' => 1,
                'originalPrice' => 1,
                'discountPercent' => 99,
                'discountAmount' => 500,
                'subtotal' => 2,
                'taxRate' => 0,
                'taxAmount' => 0,
                'total' => 2,
            ]))
            ->post('/api/v1/cart-items')
            ->assertCreated();

        $item = CartItem::where('shopping_cart_id', $cart->id)->firstOrFail();
        $this->assertEquals(1000.0, (float) $item->unit_price);
        $this->assertEquals(0.0, (float) $item->discount_amount);
        $this->assertEquals(0.0, (float) $item->discount_percent);
        $this->assertEquals(16.0, (float) $item->tax_rate);
        $this->assertEquals(2000.0, (float) $item->subtotal);
        $this->assertEquals(320.0, (float) $item->tax_amount);
        $this->assertEquals(2320.0, (float) $item->total);
    }

    public function test_adding_with_only_product_and_quantity_uses_the_catalog_price_and_tax(): void
    {
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer);
        $product = $this->product(['price' => 250.5, 'iva' => false, 'tax_rate' => 0]);

        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cart-items')
            ->withData($this->payload($cart, $product, ['quantity' => 3]))
            ->post('/api/v1/cart-items')
            ->assertCreated()
            ->assertJsonPath('data.attributes.unitPrice', 250.5);

        $item = CartItem::where('shopping_cart_id', $cart->id)->firstOrFail();
        $this->assertEquals(751.5, (float) $item->subtotal);
        $this->assertEquals(0.0, (float) $item->tax_amount);
        $this->assertEquals(751.5, (float) $item->total);
    }

    public function test_customer_cannot_change_the_price_of_an_item_already_in_the_cart(): void
    {
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer);
        $product = $this->product();
        $item = CartItem::create([
            'shopping_cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => 1000, 'original_price' => 1000, 'discount_percent' => 0, 'discount_amount' => 0,
            'subtotal' => 1000, 'tax_rate' => 16, 'tax_amount' => 160, 'total' => 1160,
        ]);

        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cart-items')
            ->withData(['type' => 'cart-items', 'id' => (string) $item->id, 'attributes' => [
                'quantity' => 3,
                'unitPrice' => 1,
                'discountAmount' => 999,
                'taxRate' => 0,
                'subtotal' => 3,
                'taxAmount' => 0,
                'total' => 3,
            ]])
            ->patch("/api/v1/cart-items/{$item->id}")
            ->assertOk();

        $item->refresh();
        $this->assertEquals(3.0, (float) $item->quantity);
        $this->assertEquals(1000.0, (float) $item->unit_price);
        $this->assertEquals(0.0, (float) $item->discount_amount);
        $this->assertEquals(3000.0, (float) $item->subtotal);
        $this->assertEquals(480.0, (float) $item->tax_amount);
        $this->assertEquals(3480.0, (float) $item->total);
    }

    public function test_quote_only_product_enters_the_cart_without_price(): void
    {
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer);
        $product = $this->product(['pricing_type' => Product::PRICING_QUOTE_ONLY, 'price' => null]);

        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cart-items')
            ->withData($this->payload($cart, $product, ['quantity' => 1, 'unitPrice' => 50, 'total' => 58]))
            ->post('/api/v1/cart-items')
            ->assertCreated();

        $item = CartItem::where('shopping_cart_id', $cart->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $item->unit_price);
        $this->assertEquals(0.0, (float) $item->total);
    }
}
