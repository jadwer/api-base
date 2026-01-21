<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;
use Laravel\Sanctum\Sanctum;

/**
 * Tests for StripeController HTTP endpoints
 *
 * These tests use real Stripe API in test mode (pk_test_/sk_test_ keys).
 * The test environment should have valid Stripe test credentials.
 */
class StripeControllerTest extends TestCase
{
    private ?string $testPaymentIntentId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip tests if Stripe is not configured
        if (!config('services.stripe.secret') || !str_starts_with(config('services.stripe.secret'), 'sk_test_')) {
            $this->markTestSkipped('Stripe test credentials not configured');
        }
    }

    /**
     * Test POST /api/v1/stripe/payment-intents - create payment intent
     */
    public function test_can_create_payment_intent(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 1500.00,
            'currency' => 'mxn',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'client_secret',
                    'status',
                    'amount',
                    'currency',
                    'transaction_id',
                ],
            ]);

        // Store for later tests
        $this->testPaymentIntentId = $response->json('data.id');

        // Verify it starts with 'pi_' (real Stripe payment intent)
        $this->assertStringStartsWith('pi_', $response->json('data.id'));
        $this->assertEquals('requires_payment_method', $response->json('data.status'));
        $this->assertEquals(150000, $response->json('data.amount')); // 1500.00 * 100
    }

    /**
     * Test POST /api/v1/stripe/payment-intents - validation errors
     */
    public function test_create_payment_intent_requires_amount(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'currency' => 'mxn',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents - minimum amount validation
     */
    public function test_create_payment_intent_validates_minimum_amount(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 5, // Below minimum of 10
            'currency' => 'mxn',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test GET /api/v1/stripe/payment-intents/{id} - retrieve payment intent
     */
    public function test_can_retrieve_payment_intent(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        // First create a payment intent
        $createResponse = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 500.00,
            'currency' => 'mxn',
        ]);

        $paymentIntentId = $createResponse->json('data.id');

        // Now retrieve it
        $response = $this->getJson("/api/v1/stripe/payment-intents/{$paymentIntentId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'amount',
                    'currency',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $paymentIntentId,
                    'amount' => 50000,
                ],
            ]);
    }

    /**
     * Test GET /api/v1/stripe/payment-intents/{id} - not found
     */
    public function test_retrieve_nonexistent_payment_intent_returns_404(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->getJson('/api/v1/stripe/payment-intents/pi_invalid_id_12345');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Payment intent not found',
            ]);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents/{id}/cancel
     */
    public function test_can_cancel_payment_intent(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        // Create a payment intent
        $createResponse = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 300.00,
            'currency' => 'mxn',
        ]);

        $paymentIntentId = $createResponse->json('data.id');

        // Cancel it
        $response = $this->postJson("/api/v1/stripe/payment-intents/{$paymentIntentId}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $paymentIntentId,
                    'status' => 'canceled',
                ],
            ]);
    }

    /**
     * Test endpoints require authentication
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 100.00,
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/stripe/payment-intents/pi_test');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test/confirm');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test/capture');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test/cancel');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/stripe/refunds', [
            'payment_intent_id' => 'pi_test',
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test creating payment intent with metadata
     */
    public function test_can_create_payment_intent_with_metadata(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 1000.00,
            'currency' => 'mxn',
            'metadata' => [
                'order_id' => '12345',
                'customer_name' => 'Test Customer',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertStringStartsWith('pi_', $response->json('data.id'));
    }

    /**
     * Test creating payment intent with manual capture
     */
    public function test_can_create_payment_intent_with_manual_capture(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 2000.00,
            'currency' => 'mxn',
            'capture_method' => 'manual',
        ]);

        $response->assertStatus(201);
        $this->assertStringStartsWith('pi_', $response->json('data.id'));
    }

    /**
     * Test POST /api/v1/stripe/refunds - validation errors
     */
    public function test_create_refund_requires_payment_intent_id(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/refunds', [
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_intent_id']);
    }

    /**
     * Test POST /api/v1/stripe/refunds - invalid reason
     */
    public function test_create_refund_validates_reason(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/refunds', [
            'payment_intent_id' => 'pi_test_123',
            'reason' => 'invalid_reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }
}
