<?php

namespace Modules\Ecommerce\Tests\Integration;

use Mockery;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\StripeService;

/**
 * Tests de INVARIANTE del webhook de Stripe (Fase 2.7).
 *
 * Flujo real de punta a punta:
 *   1. La orden nace del checkout real (POST /shopping-carts/{id}/checkout
 *      con payment_intent_id).
 *   2. El PaymentTransaction nace del endpoint real POST /v1/stripe/payment-intents;
 *      SOLO se mockea la frontera con Stripe (StripeService::createPaymentIntent,
 *      que es la llamada de red). syncPaymentIntentToTransaction corre real.
 *   3. El webhook payment_intent.succeeded entra por POST /api/webhooks/stripe
 *      con un payload realista y una firma HMAC GENUINA calculada con un
 *      webhook_secret de prueba: la verificacion de firma corre real, sin mocks.
 *
 * QUEUE_CONNECTION=sync (phpunit.xml): los listeners ShouldQueue corren inline,
 * asi que si el efecto no ocurre no es por falta de queue worker (hallazgo H1
 * historico queda descartado como causa en este entorno).
 */
class StripeWebhookInvariantTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_invariant_test_secret';

    private Warehouse $warehouse;

    /**
     * Mock parcial UNICO por test. Laravel cachea la instancia del controller
     * por ruta dentro del mismo test, asi que re-bindear un segundo mock no
     * llega al controller ya resuelto: las expectativas se encolan sobre el
     * mismo objeto (Mockery las consume en orden de declaracion).
     */
    private ?\Mockery\MockInterface $stripeBoundary = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    /**
     * Checkout real: crea la orden desde un carrito con stock suficiente.
     */
    private function checkoutOrder(User $user, string $paymentIntentId): SalesOrder
    {
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
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

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'billing_address' => ['street' => 'Calle Webhook 1'],
                'shipping_address' => ['street' => 'Calle Webhook 1'],
                'payment_intent_id' => $paymentIntentId,
            ]);
        $response->assertStatus(201);

        return SalesOrder::findOrFail((int) $response->json('data.id'));
    }

    /**
     * Crea el PaymentTransaction por el flujo real del endpoint de payment
     * intents, mockeando UNICAMENTE la llamada de red a Stripe.
     */
    private function createPaymentIntentViaEndpoint(User $user, SalesOrder $order, string $paymentIntentId): PaymentTransaction
    {
        $fakeIntent = \Stripe\PaymentIntent::constructFrom([
            'id' => $paymentIntentId,
            'object' => 'payment_intent',
            'status' => 'requires_payment_method',
            'amount' => (int) round($order->total_amount * 100),
            'currency' => 'mxn',
            'client_secret' => $paymentIntentId . '_secret_test',
            'payment_method_types' => ['card'],
            'metadata' => ['order_id' => (string) $order->id],
            'charges' => ['object' => 'list', 'data' => []],
        ]);

        // Frontera Stripe: solo createPaymentIntent (llamada HTTP al API) se
        // mockea; el resto del servicio (sync, webhook) corre real.
        if ($this->stripeBoundary === null) {
            $this->stripeBoundary = Mockery::mock(StripeService::class)->makePartial();
            $this->app->instance(StripeService::class, $this->stripeBoundary);
        }
        $this->stripeBoundary->shouldReceive('createPaymentIntent')->once()->andReturn($fakeIntent);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stripe/payment-intents', [
                'amount' => $order->total_amount,
                'currency' => 'mxn',
                'metadata' => ['order_id' => (string) $order->id],
            ]);
        $response->assertStatus(201);

        $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntentId)->firstOrFail();

        // Sanidad del flujo real: la transaccion quedo ligada a la orden y pendiente
        $this->assertSame($order->id, $transaction->sales_order_id);
        $this->assertSame('pending', $transaction->status);

        return $transaction;
    }

    /**
     * POST al webhook con cuerpo crudo y firma HMAC real (t.v1) del secret de
     * prueba. Nada de la verificacion se mockea.
     */
    private function postStripeWebhook(array $event): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode($event);
        $timestamp = time();
        $signature = 't=' . $timestamp . ',v1='
            . hash_hmac('sha256', $timestamp . '.' . $payload, self::WEBHOOK_SECRET);

        return $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $payload);
    }

    private function succeededEvent(string $eventId, string $paymentIntentId, int $amountCents): array
    {
        return [
            'id' => $eventId,
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => ['id' => null, 'idempotency_key' => null],
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => $amountCents,
                    'amount_received' => $amountCents,
                    'currency' => 'mxn',
                    'payment_method_types' => ['card'],
                    'charges' => [
                        'object' => 'list',
                        'data' => [['id' => 'ch_' . substr($paymentIntentId, 3), 'object' => 'charge']],
                    ],
                ],
            ],
        ];
    }

    public function test_webhook_succeeded_marks_payment_transaction_captured(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_capture');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_capture');

        $response = $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_capture_1', 'pi_inv_capture', 20000)
        );
        $response->assertStatus(200);

        // Invariante en base: la transaccion quedo capturada y ligada a la orden
        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_capture')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        $this->assertNotNull($transaction->captured_at);
        $this->assertSame($order->id, $transaction->sales_order_id);
    }

    /**
     * HALLAZGO esperado si este test queda en rojo: el flujo esperado del
     * negocio es que payment_intent.succeeded mueva la orden a pagada, pero
     * StripeService::handlePaymentIntentSucceeded solo actualiza
     * payment_transactions y dispara PaymentCaptured, cuyo unico listener
     * (GenerateCFDIAfterPayment) requiere un ar_invoice_id que en el flujo
     * e-commerce no existe todavia (la AR nace en la entrega). Nadie toca
     * sales_orders: financial_status nace en 'not_invoiced' (default de la
     * migracion) y se queda ahi; los unicos escritores del campo son los
     * listeners de facturacion/cancelacion, ninguno reacciona al pago Stripe.
     * Con QUEUE=sync queda demostrado que la causa NO es el queue worker (H1):
     * el eslabon webhook -> orden simplemente no existe.
     */
    public function test_webhook_succeeded_marks_order_as_paid(): void
    {
        // Fase 2.7: skip DELIBERADO, no verde falso. El eslabon webhook -> orden
        // NO existe en el codigo (bug real H-C, patron raiz 2: evento disparado
        // sin listener que escriba en la orden) y decidir COMO se marca la orden
        // pagada (campo, estado, quien crea la AR en e-commerce) es diseno
        // pendiente con Gabino. Quitar el skip cuando exista el eslabon; el
        // cuerpo ya describe el comportamiento esperado.
        $this->markTestSkipped(
            'BUG REAL pendiente de diseno: payment_intent.succeeded no propaga '
            . 'el cobro a sales_orders (solo payment_transactions). Ver '
            . 'PLAN_FASE_2_7_TESTS.md hallazgo H-C.'
        );

        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_paid');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_paid');

        // Estado post-checkout: sin factura y sin pago reflejado
        $this->assertSame('not_invoiced', $order->financial_status);

        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_paid_1', 'pi_inv_paid', 20000)
        )->assertStatus(200);

        $order->refresh();

        // Invariante: una orden cuyo pago Stripe quedo capturado debe
        // reflejar el cobro (financial_status = paid).
        $this->assertSame(
            'paid',
            $order->financial_status,
            'La orden sigue financial_status=' . $order->financial_status
                . ' despues de capturar su pago Stripe: el webhook no propaga el cobro a sales_orders'
        );
    }

    public function test_webhook_retry_with_same_event_does_not_duplicate_effects(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_retry');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_retry');

        $event = $this->succeededEvent('evt_inv_retry_1', 'pi_inv_retry', 20000);

        $this->postStripeWebhook($event)->assertStatus(200);

        $transactionsAfterFirst = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->count();
        $ordersAfterFirst = SalesOrder::count();
        $cfdisAfterFirst = CFDIInvoice::count();
        $capturedAtFirst = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')
            ->value('captured_at');

        // Reintento de Stripe: mismo event id, mismo payload
        $this->postStripeWebhook($event)->assertStatus(200);

        // Invariantes de idempotencia contra la base
        $this->assertSame(
            $transactionsAfterFirst,
            PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->count(),
            'El reintento no debe crear payment_transactions adicionales'
        );
        $this->assertSame(1, $transactionsAfterFirst);
        $this->assertSame($ordersAfterFirst, SalesOrder::count(), 'El reintento no debe crear ordenes');
        $this->assertSame($cfdisAfterFirst, CFDIInvoice::count(), 'El reintento no debe duplicar CFDIs');

        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        $this->assertNotNull($capturedAtFirst);
    }

    public function test_webhook_for_other_payment_intent_does_not_touch_this_order(): void
    {
        $userA = $this->getCustomerUser();
        $orderA = $this->checkoutOrder($userA, 'pi_inv_alpha');
        $this->createPaymentIntentViaEndpoint($userA, $orderA, 'pi_inv_alpha');

        $userB = User::factory()->create(['email' => 'webhook-b@example.com']);
        $userB->assignRole('customer');
        $orderB = $this->checkoutOrder($userB, 'pi_inv_beta');
        $this->createPaymentIntentViaEndpoint($userB, $orderB, 'pi_inv_beta');

        // Solo se paga la orden B
        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_beta_1', 'pi_inv_beta', 20000)
        )->assertStatus(200);

        // B capturada, A intacta
        $this->assertSame(
            'captured',
            PaymentTransaction::where('payment_intent_id', 'pi_inv_beta')->value('status')
        );
        $transactionA = PaymentTransaction::where('payment_intent_id', 'pi_inv_alpha')->firstOrFail();
        $this->assertSame('pending', $transactionA->status);
        $this->assertNull($transactionA->captured_at);

        $orderA->refresh();
        $this->assertSame('pending', $orderA->status);
    }
}
