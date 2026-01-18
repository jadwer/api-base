<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Services\StripeService;
use Modules\Billing\Models\PaymentTransaction;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\Refund;

/**
 * Tests for StripeController HTTP endpoints
 *
 * These tests mock the StripeService to avoid hitting the real Stripe API.
 * For integration tests with real Stripe, see StripeIntegrationTest.php
 */
class StripeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getAdminUser(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    /**
     * Test POST /api/v1/stripe/payment-intents - create payment intent
     */
    public function test_can_create_payment_intent(): void
    {
        // Mock StripeService
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_123';
        $mockPaymentIntent->client_secret = 'pi_test_123_secret_abc';
        $mockPaymentIntent->status = 'requires_payment_method';
        $mockPaymentIntent->amount = 150000;
        $mockPaymentIntent->currency = 'mxn';

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createPaymentIntent')
            ->once()
            ->with(1500.00, 'mxn', [], Mockery::any())
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once()
            ->andReturn(PaymentTransaction::factory()->make(['id' => 1]));

        $this->app->instance(StripeService::class, $mockStripeService);

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
            ])
            ->assertJson([
                'data' => [
                    'id' => 'pi_test_123',
                    'status' => 'requires_payment_method',
                ],
            ]);
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
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_456';
        $mockPaymentIntent->status = 'requires_payment_method';
        $mockPaymentIntent->amount = 50000;
        $mockPaymentIntent->currency = 'mxn';
        $mockPaymentIntent->payment_method = null;
        $mockPaymentIntent->capture_method = 'automatic';
        $mockPaymentIntent->client_secret = 'pi_test_456_secret_xyz';
        $mockPaymentIntent->created = 1704067200;
        $mockPaymentIntent->metadata = collect([]);
        $mockPaymentIntent->shouldReceive('toArray')->andReturn([]);

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('retrievePaymentIntent')
            ->once()
            ->with('pi_test_456')
            ->andReturn($mockPaymentIntent);

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->getJson('/api/v1/stripe/payment-intents/pi_test_456');

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
                    'id' => 'pi_test_456',
                    'amount' => 50000,
                ],
            ]);
    }

    /**
     * Test GET /api/v1/stripe/payment-intents/{id} - not found
     */
    public function test_retrieve_nonexistent_payment_intent_returns_404(): void
    {
        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('retrievePaymentIntent')
            ->once()
            ->with('pi_invalid')
            ->andThrow(new \Exception('No such payment intent'));

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->getJson('/api/v1/stripe/payment-intents/pi_invalid');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Payment intent not found',
            ]);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents/{id}/confirm
     */
    public function test_can_confirm_payment_intent(): void
    {
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_789';
        $mockPaymentIntent->status = 'succeeded';
        $mockPaymentIntent->next_action = null;

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('confirmPaymentIntent')
            ->once()
            ->with('pi_test_789', Mockery::any())
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once();

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test_789/confirm', [
            'payment_method' => 'pm_card_visa',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 'pi_test_789',
                    'status' => 'succeeded',
                ],
            ]);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents/{id}/capture
     */
    public function test_can_capture_payment_intent(): void
    {
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_capture';
        $mockPaymentIntent->status = 'succeeded';
        $mockPaymentIntent->amount_received = 100000;

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('capturePaymentIntent')
            ->once()
            ->with('pi_test_capture', null)
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once();

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test_capture/capture');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 'pi_test_capture',
                    'status' => 'succeeded',
                ],
            ]);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents/{id}/capture with partial amount
     */
    public function test_can_capture_payment_intent_with_partial_amount(): void
    {
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_partial';
        $mockPaymentIntent->status = 'succeeded';
        $mockPaymentIntent->amount_received = 50000;

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('capturePaymentIntent')
            ->once()
            ->with('pi_test_partial', 50000) // 500.00 * 100
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once();

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test_partial/capture', [
            'amount_to_capture' => 500.00,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test POST /api/v1/stripe/payment-intents/{id}/cancel
     */
    public function test_can_cancel_payment_intent(): void
    {
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_cancel';
        $mockPaymentIntent->status = 'canceled';

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('cancelPaymentIntent')
            ->once()
            ->with('pi_test_cancel')
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once();

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents/pi_test_cancel/cancel');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 'pi_test_cancel',
                    'status' => 'canceled',
                ],
            ]);
    }

    /**
     * Test POST /api/v1/stripe/refunds - create refund
     */
    public function test_can_create_refund(): void
    {
        // Create a transaction to update
        $transaction = PaymentTransaction::factory()->create([
            'gateway' => 'stripe',
            'payment_intent_id' => 'pi_test_refund',
            'status' => 'captured',
        ]);

        $mockRefund = Mockery::mock(Refund::class);
        $mockRefund->id = 're_test_123';
        $mockRefund->status = 'succeeded';
        $mockRefund->amount = 25000;
        $mockRefund->currency = 'mxn';
        $mockRefund->payment_intent = 'pi_test_refund';

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createRefund')
            ->once()
            ->with('pi_test_refund', 25000, 'requested_by_customer')
            ->andReturn($mockRefund);

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/refunds', [
            'payment_intent_id' => 'pi_test_refund',
            'amount' => 250.00,
            'reason' => 'requested_by_customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'amount',
                    'currency',
                    'payment_intent',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => 're_test_123',
                    'status' => 'succeeded',
                ],
            ]);

        // Verify transaction was updated
        $transaction->refresh();
        $this->assertEquals('refunded', $transaction->status);
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
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_meta';
        $mockPaymentIntent->client_secret = 'pi_test_meta_secret';
        $mockPaymentIntent->status = 'requires_payment_method';
        $mockPaymentIntent->amount = 100000;
        $mockPaymentIntent->currency = 'mxn';

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createPaymentIntent')
            ->once()
            ->with(1000.00, 'mxn', ['order_id' => '12345', 'customer_name' => 'Test'], Mockery::any())
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once()
            ->andReturn(PaymentTransaction::factory()->make(['id' => 1]));

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 1000.00,
            'currency' => 'mxn',
            'metadata' => [
                'order_id' => '12345',
                'customer_name' => 'Test',
            ],
        ]);

        $response->assertStatus(201);
    }

    /**
     * Test creating payment intent with manual capture
     */
    public function test_can_create_payment_intent_with_manual_capture(): void
    {
        $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
        $mockPaymentIntent->id = 'pi_test_manual';
        $mockPaymentIntent->client_secret = 'pi_test_manual_secret';
        $mockPaymentIntent->status = 'requires_payment_method';
        $mockPaymentIntent->amount = 200000;
        $mockPaymentIntent->currency = 'mxn';

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createPaymentIntent')
            ->once()
            ->with(2000.00, 'mxn', [], Mockery::on(function ($options) {
                return isset($options['capture_method']) && $options['capture_method'] === 'manual';
            }))
            ->andReturn($mockPaymentIntent);

        $mockStripeService->shouldReceive('syncPaymentIntentToTransaction')
            ->once()
            ->andReturn(PaymentTransaction::factory()->make(['id' => 1]));

        $this->app->instance(StripeService::class, $mockStripeService);

        Sanctum::actingAs($this->getAdminUser());

        $response = $this->postJson('/api/v1/stripe/payment-intents', [
            'amount' => 2000.00,
            'currency' => 'mxn',
            'capture_method' => 'manual',
        ]);

        $response->assertStatus(201);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
