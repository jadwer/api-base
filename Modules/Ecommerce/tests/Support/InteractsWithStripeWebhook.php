<?php

namespace Modules\Ecommerce\Tests\Support;

use Mockery;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Billing\Services\StripeService;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;

/**
 * Maquinaria compartida de los tests de invariante del flujo Stripe
 * (Fase 2.7 / DESIGN_ECOMMERCE_PAGO_STOCK), usada por
 * StripeWebhookInvariantTest y CheckoutStockInvariantTest:
 *
 * - El PaymentTransaction nace del endpoint real POST /v1/stripe/payment-intents;
 *   SOLO se mockea la frontera con Stripe (StripeService::createPaymentIntent,
 *   que es la llamada de red). syncPaymentIntentToTransaction corre real.
 * - Los webhooks entran por POST /api/webhooks/stripe con payload realista y
 *   firma HMAC GENUINA (t.v1) calculada con el webhook_secret de prueba: la
 *   verificacion de firma corre real, sin mocks.
 *
 * El test que use el trait debe fijar en su setUp:
 *   config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
 */
trait InteractsWithStripeWebhook
{
    protected const WEBHOOK_SECRET = 'whsec_invariant_test_secret';

    /**
     * Mock parcial UNICO por test. Laravel cachea la instancia del controller
     * por ruta dentro del mismo test, asi que re-bindear un segundo mock no
     * llega al controller ya resuelto: las expectativas se encolan sobre el
     * mismo objeto (Mockery las consume en orden de declaracion).
     */
    protected ?\Mockery\MockInterface $stripeBoundary = null;

    /**
     * Crea el PaymentTransaction por el flujo real del endpoint de payment
     * intents, mockeando UNICAMENTE la llamada de red a Stripe.
     *
     * $amountCents permite simular un cliente que crea el intent por un monto
     * DISTINTO al total de la orden (escenario del test de mismatch); por
     * defecto el intent cuadra con order.total_amount.
     */
    protected function createPaymentIntentViaEndpoint(
        User $user,
        SalesOrder $order,
        string $paymentIntentId,
        ?int $amountCents = null
    ): PaymentTransaction {
        $amountCents ??= (int) round($order->total_amount * 100);

        $fakeIntent = \Stripe\PaymentIntent::constructFrom([
            'id' => $paymentIntentId,
            'object' => 'payment_intent',
            'status' => 'requires_payment_method',
            'amount' => $amountCents,
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
                'amount' => $amountCents / 100,
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
    protected function postStripeWebhook(array $event): \Illuminate\Testing\TestResponse
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

    protected function succeededEvent(string $eventId, string $paymentIntentId, int $amountCents): array
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

    /**
     * Evento charge.refunded: StripeService::handleChargeRefunded resuelve la
     * transaccion por transaction_id == charge.id (el charge id que dejo el
     * succeeded en payment_transactions.transaction_id).
     */
    protected function chargeRefundedEvent(string $eventId, string $chargeId, int $amountCents): array
    {
        return [
            'id' => $eventId,
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => ['id' => null, 'idempotency_key' => null],
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => $chargeId,
                    'object' => 'charge',
                    'amount' => $amountCents,
                    'amount_refunded' => $amountCents,
                    'currency' => 'mxn',
                    'refunded' => true,
                    'refunds' => [
                        'object' => 'list',
                        'data' => [
                            [
                                'id' => 're_' . substr($chargeId, 3),
                                'object' => 'refund',
                                'amount' => $amountCents,
                                'reason' => 'requested_by_customer',
                                'status' => 'succeeded',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
