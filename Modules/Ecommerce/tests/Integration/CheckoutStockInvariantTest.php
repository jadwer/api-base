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
use Modules\Ecommerce\Tests\Support\InteractsWithStripeWebhook;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * Tests de INVARIANTE del checkout contra el stock real (Fase 2.7 +
 * DESIGN_ECOMMERCE_PAGO_STOCK).
 *
 * Invariante bajo prueba: el checkout de e-commerce no debe crear ordenes
 * de venta por cantidades que el inventario no puede respaldar.
 *
 * Contexto de diseno (H-A): la reserva atomica en el checkout fue revertida a
 * proposito; el compromiso de stock nace AL PAGAR. El checkout valida contra
 * CommittedStockService::availableForSale = fisico - reservado - comprometido,
 * donde comprometido = items de ordenes payment_status='paid' aun no
 * entregadas. Las ordenes del dashboard sin pago NO comprometen (decision
 * deliberada: ese canal lo controla el staff y sus pending eternas
 * bloquearian el catalogo).
 *
 * Estos tests verifican el contrato vigente contra la BASE (sales_orders,
 * sales_order_items, shopping_carts, stock), no solo la respuesta HTTP. La
 * orden pagada del arrange se paga por el CAMINO REAL: payment intent por el
 * endpoint + webhook payment_intent.succeeded con firma HMAC genuina (trait
 * InteractsWithStripeWebhook, compartido con StripeWebhookInvariantTest).
 */
class CheckoutStockInvariantTest extends TestCase
{
    use InteractsWithStripeWebhook;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    /**
     * Crea un producto con stock exacto en el almacen del test.
     */
    private function productWithStock(float $quantity, float $reserved = 0): Product
    {
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
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'status' => 'active',
        ]);

        return $product;
    }

    /**
     * Crea un carrito activo del usuario con un item del producto dado.
     * Los montos se fijan explicitos (el observer solo recalcula en multi-moneda).
     */
    private function cartWithItem(User $user, Product $product, float $quantity): ShoppingCart
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

        $subtotal = round(100 * $quantity, 2);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => $subtotal,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
        ]);

        return $cart;
    }

    private function checkout(User $user, ShoppingCart $cart, ?string $paymentIntentId = null)
    {
        $payload = [
            'billing_address' => ['street' => 'Calle Invariante 1'],
            'shipping_address' => ['street' => 'Calle Invariante 1'],
        ];
        if ($paymentIntentId !== null) {
            $payload['payment_intent_id'] = $paymentIntentId;
        }

        return $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", $payload);
    }

    public function test_checkout_rejected_when_quantity_exceeds_available_stock(): void
    {
        $user = $this->getCustomerUser();
        $product = $this->productWithStock(quantity: 5);
        $cart = $this->cartWithItem($user, $product, quantity: 10);

        $ordersBefore = SalesOrder::count();

        $response = $this->checkout($user, $cart);

        // Rechazo explicito con detalle del faltante y CTA de cotizacion
        $response->assertStatus(422);
        $response->assertJsonPath('insufficient_items.0.product_id', $product->id);
        $response->assertJsonPath('insufficient_items.0.product_name', $product->name);
        $this->assertEquals(10.0, (float) $response->json('insufficient_items.0.requested'));
        $this->assertEquals(5.0, (float) $response->json('insufficient_items.0.available'));
        $this->assertTrue((bool) $response->json('quote_cta'), 'El 422 debe traer quote_cta para ofrecer cotizar');

        // Invariante en base: NO se creo ninguna orden ni item de orden
        $this->assertSame($ordersBefore, SalesOrder::count(), 'El checkout rechazado no debe crear sales_orders');
        $this->assertDatabaseMissing('sales_order_items', ['product_id' => $product->id]);

        // El carrito queda intacto y reutilizable
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $cart->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('cart_items', [
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        // El stock no se toco
        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(5.0, (float) $stock->quantity);
        $this->assertEquals(0.0, (float) $stock->reserved_quantity);
    }

    public function test_checkout_succeeds_when_quantity_equals_available_stock(): void
    {
        $user = $this->getCustomerUser();
        // Contact ya existente para el usuario (evita crear uno on-the-fly)
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
        ]);

        $product = $this->productWithStock(quantity: 10);
        $cart = $this->cartWithItem($user, $product, quantity: 10);

        $response = $this->checkout($user, $cart);

        $response->assertStatus(201);
        $orderId = (int) $response->json('data.id');

        // La orden y sus items existen en base con las cantidades del carrito
        $this->assertDatabaseHas('sales_orders', [
            'id' => $orderId,
            'status' => 'pending',
        ]);
        $item = \Illuminate\Support\Facades\DB::table('sales_order_items')
            ->where('sales_order_id', $orderId)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($item, 'Debe existir el sales_order_item del producto');
        $this->assertEquals(10.0, (float) $item->quantity);

        // El carrito quedo convertido
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $cart->id,
            'status' => 'converted',
        ]);

        // Contrato vigente (compromiso al PAGAR, no al ordenar): el checkout NO
        // descuenta ni reserva stock; el fisico sale en la entrega.
        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(10.0, (float) $stock->quantity);
        $this->assertEquals(0.0, (float) $stock->reserved_quantity);
    }

    /**
     * DESIGN_ECOMMERCE_PAGO_STOCK (H-A): el oversell secuencial quedo cerrado.
     * La compra A se PAGA por el camino real (intent por endpoint + webhook
     * succeeded firmado); con eso la orden compromete su cantidad y el
     * disponible que ve el checkout B es 0: rechazo 422 con CTA de cotizacion,
     * sin orden creada para B.
     */
    public function test_second_checkout_of_same_limited_stock_cannot_oversell(): void
    {
        $product = $this->productWithStock(quantity: 10);

        $userA = $this->getCustomerUser();
        $userB = User::factory()->create(['email' => 'oversell-b@example.com']);
        $userB->assignRole('customer');

        $cartA = $this->cartWithItem($userA, $product, quantity: 10);
        $cartB = $this->cartWithItem($userB, $product, quantity: 10);

        // Primer checkout: toma todo el stock disponible (aun sin pagar)
        $responseA = $this->checkout($userA, $cartA, paymentIntentId: 'pi_oversell_a');
        $responseA->assertStatus(201);
        $orderA = SalesOrder::findOrFail((int) $responseA->json('data.id'));

        // Pago REAL de la orden A: payment intent por el endpoint + webhook
        // payment_intent.succeeded con firma HMAC genuina.
        $this->createPaymentIntentViaEndpoint($userA, $orderA, 'pi_oversell_a');
        $this->postStripeWebhook(
            $this->succeededEvent(
                'evt_oversell_a_1',
                'pi_oversell_a',
                (int) round($orderA->total_amount * 100)
            )
        )->assertStatus(200);

        $orderA->refresh();
        $this->assertSame(
            'paid',
            $orderA->payment_status,
            'Arrange: la orden A debio quedar pagada por el webhook para comprometer stock'
        );

        $ordersAfterA = SalesOrder::count();

        // Segundo checkout del MISMO stock: la invariante exige rechazo
        $responseB = $this->checkout($userB, $cartB);

        $committed = (float) \Illuminate\Support\Facades\DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_order_items.product_id', $product->id)
            ->whereNotIn('sales_orders.status', ['cancelled'])
            ->sum('sales_order_items.quantity');

        $this->assertEquals(
            422,
            $responseB->getStatusCode(),
            'Oversell: el segundo checkout del mismo stock limitado debio rechazarse '
                . "(respondio {$responseB->getStatusCode()}; comprometido={$committed} vs stock=10)"
        );
        $responseB->assertJsonPath('insufficient_items.0.product_id', $product->id);
        $this->assertEquals(10.0, (float) $responseB->json('insufficient_items.0.requested'));
        $this->assertEquals(0.0, (float) $responseB->json('insufficient_items.0.available'));
        $this->assertTrue((bool) $responseB->json('quote_cta'), 'El rechazo debe ofrecer el CTA de cotizacion');

        // Invariantes en base: no nacio orden para B y lo comprometido no
        // excede el stock fisico.
        $this->assertSame($ordersAfterA, SalesOrder::count(), 'El checkout rechazado de B no debe crear sales_orders');
        $this->assertSame(
            1,
            \Illuminate\Support\Facades\DB::table('sales_order_items')
                ->where('product_id', $product->id)
                ->count(),
            'Solo debe existir el item de la orden A'
        );
        $this->assertLessThanOrEqual(
            10.0,
            $committed,
            'La cantidad comprometida en ordenes vivas no debe exceder el stock disponible'
        );
    }

    /**
     * Alcance deliberado del comprometido (decision de Gabino 2026-07-18): una
     * orden del DASHBOARD confirmada pero SIN pago NO compromete stock. El
     * filtro duro es payment_status='paid'; las ordenes del canal interno
     * (cotizacion, historicamente pending/confirmed eternas) las controla el
     * staff y no deben bloquear el catalogo.
     *
     * Arrange con factory legitimo aqui: se modela una orden creada por el
     * staff en el dashboard; payment_status queda en su default 'unpaid'.
     */
    public function test_confirmed_dashboard_order_without_payment_does_not_commit_stock(): void
    {
        $product = $this->productWithStock(quantity: 10);

        // Orden del dashboard: confirmada, sin pago (payment_status default unpaid)
        $dashboardCustomer = Contact::factory()->create([
            'is_customer' => true,
            'status' => 'active',
        ]);
        $dashboardOrder = SalesOrder::factory()->create([
            'contact_id' => $dashboardCustomer->id,
            'status' => 'confirmed',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $dashboardOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'discount' => 0,
        ]);
        $this->assertSame('unpaid', $dashboardOrder->fresh()->payment_status);

        // Checkout ecommerce de TODO el stock fisico: debe proceder porque la
        // orden del dashboard sin pago no compromete.
        $user = $this->getCustomerUser();
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
        ]);
        $cart = $this->cartWithItem($user, $product, quantity: 10);

        $response = $this->checkout($user, $cart);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sales_orders', [
            'id' => (int) $response->json('data.id'),
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $cart->id,
            'status' => 'converted',
        ]);
    }
}
