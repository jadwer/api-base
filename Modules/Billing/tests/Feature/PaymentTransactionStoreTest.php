<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Sales\Models\SalesOrder;

class PaymentTransactionStoreTest extends TestCase
{
    /**
     * Test admin can create a payment transaction.
     */
    public function test_admin_can_create_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $checkoutSession = CheckoutSession::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'checkoutSessionId' => $checkoutSession->id,
                'gateway' => 'stripe',
                'paymentIntentId' => 'pi_test_' . uniqid(),
                'amount' => 1500.00,
                'currency' => 'MXN',
                'status' => 'pending',
                'paymentMethod' => 'card',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'payment-transactions',
                    'attributes' => [
                        'gateway' => 'stripe',
                        'amount' => 1500.00,
                        'currency' => 'MXN',
                        'status' => 'pending',
                        'paymentMethod' => 'card',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway' => 'stripe',
            'amount' => 1500.00,
            'currency' => 'MXN',
            'status' => 'pending',
            'payment_method' => 'card',
            'checkout_session_id' => $checkoutSession->id,
        ]);
    }

    /**
     * Test tech can create a payment transaction.
     */
    public function test_tech_can_create_payment_transaction(): void
    {
        $user = $this->getTechUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 500.00,
                'currency' => 'USD',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertCreated();
    }

    /**
     * Test customer cannot create a payment transaction.
     */
    public function test_customer_cannot_create_payment_transaction(): void
    {
        $user = $this->getCustomerUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 500.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot create a payment transaction.
     */
    public function test_guest_cannot_create_payment_transaction(): void
    {
        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 500.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(401);
    }

    /**
     * Test amount is required.
     */
    public function test_amount_is_required(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['amount']);

        // Verify Spanish error message
        $errors = $response->json('errors');
        $amountError = collect($errors)->firstWhere('source.pointer', '/data/attributes/amount');
        $this->assertStringContainsString('obligatorio', $amountError['detail']);
    }

    /**
     * Test amount must be numeric.
     */
    public function test_amount_must_be_numeric(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 'not-a-number',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['amount']);

        $errors = $response->json('errors');
        $amountError = collect($errors)->firstWhere('source.pointer', '/data/attributes/amount');
        $this->assertStringContainsString('número', $amountError['detail']);
    }

    /**
     * Test amount must be non-negative.
     */
    public function test_amount_must_be_non_negative(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => -100.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['amount']);
    }

    /**
     * Test currency must be valid code.
     */
    public function test_currency_must_be_valid_code(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 1000.00,
                'currency' => 'INVALID',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['currency']);

        $errors = $response->json('errors');
        $currencyError = collect($errors)->firstWhere('source.pointer', '/data/attributes/currency');
        $this->assertStringContainsString('MXN, USD, EUR', $currencyError['detail']);
    }

    /**
     * Test payment intent ID must be unique.
     */
    public function test_payment_intent_id_must_be_unique(): void
    {
        $user = $this->getAdminUser();
        $existingTransaction = PaymentTransaction::factory()->create([
            'payment_intent_id' => 'pi_test_duplicate',
        ]);

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'paymentIntentId' => 'pi_test_duplicate',
                'amount' => 1000.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['paymentIntentId']);

        $errors = $response->json('errors');
        $paymentIntentError = collect($errors)->firstWhere('source.pointer', '/data/attributes/paymentIntentId');
        $this->assertStringContainsString('Ya existe', $paymentIntentError['detail']);
    }

    /**
     * Test transaction ID must be unique.
     */
    public function test_transaction_id_must_be_unique(): void
    {
        $user = $this->getAdminUser();
        $existingTransaction = PaymentTransaction::factory()->create([
            'transaction_id' => 'ch_test_duplicate',
        ]);

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'transactionId' => 'ch_test_duplicate',
                'amount' => 1000.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['transactionId']);
    }

    /**
     * Test gateway must be valid.
     */
    public function test_gateway_must_be_valid(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'gateway' => 'invalid-gateway',
                'amount' => 1000.00,
                'currency' => 'MXN',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['gateway']);

        $errors = $response->json('errors');
        $gatewayError = collect($errors)->firstWhere('source.pointer', '/data/attributes/gateway');
        $this->assertStringContainsString('stripe, conekta o paypal', $gatewayError['detail']);
    }

    /**
     * Test status must be valid.
     */
    public function test_status_must_be_valid(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 1000.00,
                'currency' => 'MXN',
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['status']);

        $errors = $response->json('errors');
        $statusError = collect($errors)->firstWhere('source.pointer', '/data/attributes/status');
        $this->assertStringContainsString('pending, authorized, captured, failed, refunded o cancelled', $statusError['detail']);
    }

    /**
     * Test card last 4 must be exactly 4 digits.
     */
    public function test_card_last4_must_be_exactly_4_digits(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'amount' => 1000.00,
                'currency' => 'MXN',
                'cardLast4' => '12345', // 5 digits instead of 4
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422)
            ->assertJsonApiValidationErrors(['cardLast4']);

        $errors = $response->json('errors');
        $cardError = collect($errors)->firstWhere('source.pointer', '/data/attributes/cardLast4');
        $this->assertStringContainsString('exactamente 4 caracteres', $cardError['detail']);
    }

    /**
     * Test can create payment transaction with all optional fields.
     */
    public function test_can_create_payment_transaction_with_all_optional_fields(): void
    {
        $user = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'salesOrderId' => $salesOrder->id,
                'gateway' => 'stripe',
                'paymentIntentId' => 'pi_test_' . uniqid(),
                'transactionId' => 'ch_test_' . uniqid(),
                'clientSecret' => 'pi_secret_test',
                'amount' => 2500.00,
                'currency' => 'USD',
                'status' => 'captured',
                'paymentMethod' => 'card',
                'cardBrand' => 'visa',
                'cardLast4' => '4242',
                'metadata' => [
                    'customer_email' => 'test@example.com',
                    'order_id' => '12345',
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'gateway' => 'stripe',
                        'amount' => 2500.00,
                        'currency' => 'USD',
                        'status' => 'captured',
                        'paymentMethod' => 'card',
                        'cardBrand' => 'visa',
                        'cardLast4' => '4242',
                    ],
                ],
            ]);
    }
}
