<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;

class PaymentTransactionUpdateTest extends TestCase
{
    /**
     * Test admin can update a payment transaction.
     */
    public function test_admin_can_update_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->pending()->create([
            'status' => 'pending',
            'amount' => 1000.00,
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'captured',
                'transactionId' => 'ch_test_updated',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'captured',
                        'transactionId' => 'ch_test_updated',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
            'status' => 'captured',
            'transaction_id' => 'ch_test_updated',
        ]);
    }

    /**
     * Test tech can update a payment transaction.
     */
    public function test_tech_can_update_payment_transaction(): void
    {
        $user = $this->getTechUser();
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'failed',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful();
    }

    /**
     * Test customer cannot update a payment transaction.
     */
    public function test_customer_cannot_update_payment_transaction(): void
    {
        $user = $this->getCustomerUser();
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'captured',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot update a payment transaction.
     */
    public function test_guest_cannot_update_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'captured',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);
    }

    /**
     * Test can update payment transaction status.
     */
    public function test_can_update_payment_transaction_status(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->pending()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'authorized',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'authorized',
                    ],
                ],
            ]);
    }

    /**
     * Test can update card information.
     */
    public function test_can_update_card_information(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create([
            'card_brand' => null,
            'card_last4' => null,
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'cardBrand' => 'mastercard',
                'cardLast4' => '5555',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'cardBrand' => 'mastercard',
                        'cardLast4' => '5555',
                    ],
                ],
            ]);
    }

    /**
     * Test can update error message for failed transaction.
     */
    public function test_can_update_error_message_for_failed_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->failed()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'errorMessage' => 'Insufficient funds.',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'errorMessage' => 'Insufficient funds.',
                    ],
                ],
            ]);
    }

    /**
     * Test can update metadata.
     */
    public function test_can_update_metadata(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create([
            'metadata' => ['original' => 'data'],
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'metadata' => [
                    'customer_email' => 'updated@example.com',
                    'notes' => 'Payment processed successfully',
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful();

        $transaction->refresh();
        $this->assertEquals('updated@example.com', $transaction->metadata['customer_email']);
        $this->assertEquals('Payment processed successfully', $transaction->metadata['notes']);
    }

    /**
     * Test cannot update amount.
     */
    public function test_cannot_update_amount(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create([
            'amount' => 1000.00,
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'amount' => 2000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        // Amount should remain unchanged
        $transaction->refresh();
        $this->assertEquals(1000.00, $transaction->amount);
    }

    /**
     * Test payment intent ID must remain unique on update.
     */
    public function test_payment_intent_id_must_remain_unique_on_update(): void
    {
        $user = $this->getAdminUser();
        $transaction1 = PaymentTransaction::factory()->create([
            'payment_intent_id' => 'pi_existing',
        ]);
        $transaction2 = PaymentTransaction::factory()->create([
            'payment_intent_id' => 'pi_test_2',
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction2->id,
            'attributes' => [
                'paymentIntentId' => 'pi_existing', // Try to use existing ID
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction2->id);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $paymentIntentError = collect($errors)->firstWhere('source.pointer', '/data/attributes/paymentIntentId');
        $this->assertNotNull($paymentIntentError);
    }

    /**
     * Test can update same payment intent ID (no change).
     */
    public function test_can_update_same_payment_intent_id(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create([
            'payment_intent_id' => 'pi_test_same',
        ]);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'paymentIntentId' => 'pi_test_same', // Same ID
                'status' => 'captured',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful();
    }

    /**
     * Test status validation on update.
     */
    public function test_status_validation_on_update(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string)$transaction->id,
            'attributes' => [
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $statusError = collect($errors)->firstWhere('source.pointer', '/data/attributes/status');
        $this->assertNotNull($statusError);
        $this->assertStringContainsString('pending, authorized, captured, failed, refunded o cancelled', $statusError['detail']);
    }

    /**
     * Test returns 404 for non-existent transaction.
     */
    public function test_returns_404_for_non_existent_transaction(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'id' => '99999',
            'attributes' => [
                'status' => 'captured',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/payment-transactions/99999');

        $response->assertStatus(404);
    }
}
