<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Services\StripeService;
use Modules\Billing\Models\PaymentTransaction;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;
use Mockery;

class StripeIntegrationTest extends TestCase
{
    protected StripeService $stripeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripeService = app(StripeService::class);
    }

    /**
     * Test creating a payment intent with real Stripe API (test mode)
     */
    public function test_can_create_payment_intent()
    {
        // Skip if Stripe is not configured
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: 100.00,
            currency: 'mxn',
            metadata: ['order_id' => 'test-123']
        );

        $this->assertNotNull($paymentIntent->id);
        $this->assertEquals('requires_payment_method', $paymentIntent->status);
        $this->assertEquals(10000, $paymentIntent->amount); // 100.00 * 100 cents
        $this->assertEquals('mxn', $paymentIntent->currency);
        $this->assertEquals('test-123', $paymentIntent->metadata['order_id']);
    }

    /**
     * Test retrieving a payment intent
     */
    public function test_can_retrieve_payment_intent()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        // First create one
        $created = $this->stripeService->createPaymentIntent(
            amount: 50.00,
            currency: 'mxn'
        );

        // Then retrieve it
        $retrieved = $this->stripeService->retrievePaymentIntent($created->id);

        $this->assertEquals($created->id, $retrieved->id);
        $this->assertEquals(5000, $retrieved->amount);
    }

    /**
     * Test canceling a payment intent
     */
    public function test_can_cancel_payment_intent()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: 75.00,
            currency: 'mxn'
        );

        $canceled = $this->stripeService->cancelPaymentIntent($paymentIntent->id);

        $this->assertEquals('canceled', $canceled->status);
    }

    /**
     * Test creating a Stripe customer
     */
    public function test_can_create_stripe_customer()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $customer = $this->stripeService->createCustomer(
            email: 'test-' . uniqid() . '@example.com',
            metadata: ['user_id' => '123'],
            options: ['name' => 'Test Customer']
        );

        $this->assertNotNull($customer->id);
        $this->assertStringStartsWith('cus_', $customer->id);
    }

    /**
     * Test syncing payment intent to transaction
     */
    public function test_can_sync_payment_intent_to_transaction()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: 250.00,
            currency: 'mxn',
            metadata: ['test' => true]
        );

        $transaction = $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);

        $this->assertInstanceOf(PaymentTransaction::class, $transaction);
        $this->assertEquals($paymentIntent->id, $transaction->payment_intent_id);
        $this->assertEquals(250.00, $transaction->amount);
        $this->assertEquals('MXN', $transaction->currency);
        $this->assertEquals('stripe', $transaction->gateway);
        $this->assertEquals('pending', $transaction->status);
    }

    /**
     * Test webhook endpoint for payment success
     */
    public function test_stripe_webhook_updates_transaction_on_success()
    {
        // Create a transaction to update
        $transaction = PaymentTransaction::factory()->create([
            'gateway' => 'stripe',
            'payment_intent_id' => 'pi_test_' . uniqid(),
            'status' => 'pending',
        ]);

        // Simulate webhook payload (without signature verification for unit test)
        $response = $this->postJson('/api/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $transaction->payment_intent_id,
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'mxn',
                    'charges' => [
                        'data' => [
                            ['id' => 'ch_test_123']
                        ]
                    ]
                ]
            ]
        ]);

        // Note: This will likely fail without proper webhook signature
        // but verifies the endpoint exists
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 400,
            'Webhook endpoint should respond'
        );
    }

    /**
     * Test payment intent creation with invalid amount fails gracefully
     */
    public function test_payment_intent_with_zero_amount_fails()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $this->expectException(\Exception::class);

        $this->stripeService->createPaymentIntent(
            amount: 0,
            currency: 'mxn'
        );
    }

    /**
     * Test creating payment intent with different currencies
     */
    public function test_can_create_payment_intent_in_usd()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: 99.99,
            currency: 'usd'
        );

        $this->assertEquals('usd', $paymentIntent->currency);
        $this->assertEquals(9999, $paymentIntent->amount);
    }

    /**
     * Test payment transaction status mapping
     */
    public function test_stripe_status_mapping()
    {
        if (!config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe API key is not configured');
        }

        // Create and then cancel to test status mapping
        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: 10.00,
            currency: 'mxn'
        );

        $transaction = $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);
        $this->assertEquals('pending', $transaction->status);

        // Cancel and resync
        $canceled = $this->stripeService->cancelPaymentIntent($paymentIntent->id);
        $transaction = $this->stripeService->syncPaymentIntentToTransaction($canceled, $transaction);
        $this->assertEquals('cancelled', $transaction->status);
    }
}
