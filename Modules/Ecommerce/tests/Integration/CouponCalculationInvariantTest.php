<?php

namespace Modules\Ecommerce\Tests\Integration;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Coupon;

/**
 * Tests de INVARIANTE del calculo de cupones (Fase 2.7).
 *
 * Todo pasa por el endpoint real POST /shopping-carts/{id}/apply-coupon y se
 * verifica lo PERSISTIDO en shopping_carts / coupons / sales_orders, no solo
 * la respuesta HTTP.
 *
 * Reglas de negocio bajo prueba (CLAUDE.md, modelo Coupon):
 *   percentage    -> descuento = subtotal * value / 100
 *   fixed_amount  -> descuento = min(value, subtotal)
 *   free_shipping -> descuento de productos = 0 (el envio se maneja aparte)
 * Cupon expirado o bajo el minimo: se rechaza y no deja rastro persistido.
 */
class CouponCalculationInvariantTest extends TestCase
{
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
    }

    /**
     * Carrito activo del usuario con subtotal exacto de 200.00 MXN
     * (1 item: 2 x 100, sin impuestos ni envio para que el numero sea limpio).
     */
    private function cartWithSubtotal200(User $user): ShoppingCart
    {
        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'session_id' => null,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
            'coupon_code' => null,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
        ]);

        // Divisa MXN explicita: ProductFactory asigna Currency::first() y si
        // no es MXN el CartItemObserver convierte precios y rompe los montos.
        $mxn = \Modules\Ecommerce\Models\Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Peso Mexicano',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['price' => 100, 'currency_id' => $mxn->id]);
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => 1000,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 200,
        ]);

        return $cart;
    }

    private function makeCoupon(array $overrides): Coupon
    {
        return Coupon::factory()->create(array_merge([
            'type' => 'percentage',
            'value' => 10,
            'min_amount' => null,
            'max_amount' => null,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'currency' => null,
        ], $overrides));
    }

    private function applyCoupon(User $user, ShoppingCart $cart, string $code)
    {
        return $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => $code,
            ]);
    }

    public function test_percentage_coupon_persists_subtotal_times_value_over_100(): void
    {
        $user = $this->getCustomerUser();
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
        ]);
        $cart = $this->cartWithSubtotal200($user);
        $coupon = $this->makeCoupon(['code' => 'INV-PCT10', 'type' => 'percentage', 'value' => 10]);

        $response = $this->applyCoupon($user, $cart, 'INV-PCT10');
        $response->assertStatus(200)->assertJsonPath('valid', true);

        // Invariante persistida en el carrito: 200 * 10 / 100 = 20.00
        $cart->refresh();
        $this->assertSame('INV-PCT10', $cart->coupon_code);
        $this->assertEquals(20.00, (float) $cart->discount_amount);

        // Uso del cupon contabilizado
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);

        // El descuento sobrevive al checkout: queda en la orden
        $checkout = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'billing_address' => ['street' => 'Calle Cupon 1'],
                'shipping_address' => ['street' => 'Calle Cupon 1'],
            ]);
        $checkout->assertStatus(201);
        $orderId = (int) $checkout->json('data.id');

        $order = \Illuminate\Support\Facades\DB::table('sales_orders')->find($orderId);
        $this->assertEquals(20.00, (float) $order->discount_total);
        $this->assertEquals(200.00, (float) $order->subtotal);
        $this->assertEquals(180.00, (float) $order->total_amount);
    }

    /**
     * HALLAZGO esperado si este test queda en rojo: la regla de negocio dice
     * fixed_amount -> min(value, subtotal) (asi lo implementa
     * Coupon::calculateDiscount), pero ShoppingCartController::calculateDiscount
     * usa $coupon->value SIN acotarlo al subtotal. Un cupon de $500 sobre un
     * carrito de $200 persiste discount_amount=500 y deja finalTotal negativo
     * (-300), que luego el checkout copia a la orden.
     */
    public function test_fixed_amount_coupon_larger_than_subtotal_caps_at_subtotal(): void
    {
        $user = $this->getCustomerUser();
        $cart = $this->cartWithSubtotal200($user);
        $this->makeCoupon(['code' => 'INV-FIX500', 'type' => 'fixed_amount', 'value' => 500]);

        $response = $this->applyCoupon($user, $cart, 'INV-FIX500');
        $response->assertStatus(200)->assertJsonPath('valid', true);

        $cart->refresh();
        $this->assertSame('INV-FIX500', $cart->coupon_code);

        // Invariante: el descuento persistido nunca excede el subtotal
        $this->assertEquals(
            200.00,
            (float) $cart->discount_amount,
            'fixed_amount debe persistir min(value, subtotal) = min(500, 200) = 200'
        );
        $this->assertGreaterThanOrEqual(
            0.0,
            $cart->finalTotal,
            'Un cupon nunca debe dejar el total del carrito en negativo'
        );
    }

    public function test_fixed_amount_coupon_below_subtotal_persists_value(): void
    {
        $user = $this->getCustomerUser();
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
        ]);
        $cart = $this->cartWithSubtotal200($user);
        $this->makeCoupon(['code' => 'INV-FIX50', 'type' => 'fixed_amount', 'value' => 50]);

        $this->applyCoupon($user, $cart, 'INV-FIX50')
            ->assertStatus(200)
            ->assertJsonPath('valid', true);

        $cart->refresh();
        $this->assertEquals(50.00, (float) $cart->discount_amount);

        // Y llega integro a la orden
        $checkout = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'billing_address' => ['street' => 'Calle Cupon 2'],
                'shipping_address' => ['street' => 'Calle Cupon 2'],
            ]);
        $checkout->assertStatus(201);

        $order = \Illuminate\Support\Facades\DB::table('sales_orders')
            ->find((int) $checkout->json('data.id'));
        $this->assertEquals(50.00, (float) $order->discount_total);
        $this->assertEquals(150.00, (float) $order->total_amount);
    }

    public function test_free_shipping_coupon_does_not_touch_product_subtotal(): void
    {
        $user = $this->getCustomerUser();
        $cart = $this->cartWithSubtotal200($user);
        $this->makeCoupon(['code' => 'INV-SHIP', 'type' => 'free_shipping', 'value' => 0]);

        $this->applyCoupon($user, $cart, 'INV-SHIP')
            ->assertStatus(200)
            ->assertJsonPath('valid', true);

        $cart->refresh();
        // El cupon queda registrado pero el descuento de productos es 0
        $this->assertSame('INV-SHIP', $cart->coupon_code);
        $this->assertEquals(0.00, (float) $cart->discount_amount);

        // El subtotal de productos y los items no se alteran
        $this->assertEquals(200.00, $cart->subtotalAmount);
        $this->assertDatabaseHas('cart_items', [
            'shopping_cart_id' => $cart->id,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
    }

    public function test_expired_coupon_rejected_and_nothing_persisted(): void
    {
        $user = $this->getCustomerUser();
        $cart = $this->cartWithSubtotal200($user);
        $coupon = $this->makeCoupon([
            'code' => 'INV-EXPIRED',
            'type' => 'percentage',
            'value' => 10,
            'expires_at' => now()->subDay(),
            'used_count' => 3,
        ]);

        $response = $this->applyCoupon($user, $cart, 'INV-EXPIRED');
        $response->assertStatus(400)->assertJsonPath('valid', false);

        // Nada persistido en el carrito ni en el contador del cupon
        $cart->refresh();
        $this->assertNull($cart->coupon_code);
        $this->assertEquals(0.00, (float) $cart->discount_amount);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 3]);
    }

    public function test_coupon_below_minimum_amount_rejected_and_nothing_persisted(): void
    {
        $user = $this->getCustomerUser();
        $cart = $this->cartWithSubtotal200($user);
        $coupon = $this->makeCoupon([
            'code' => 'INV-MIN1000',
            'type' => 'percentage',
            'value' => 10,
            'min_amount' => 1000,
        ]);

        $response = $this->applyCoupon($user, $cart, 'INV-MIN1000');
        $response->assertStatus(400)->assertJsonPath('valid', false);

        $cart->refresh();
        $this->assertNull($cart->coupon_code);
        $this->assertEquals(0.00, (float) $cart->discount_amount);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 0]);
    }
}
