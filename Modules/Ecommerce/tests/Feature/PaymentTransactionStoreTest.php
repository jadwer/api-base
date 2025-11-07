<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;

class PaymentTransactionStoreTest extends TestCase
{
    public function test_admin_can_create_payment_transaction(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'checkout_session_id' => $session->id,
                'transactionId' => 'ch_test123',
                'paymentGateway' => 'stripe',
                'paymentMethod' => 'credit_card',
                'status' => 'pending',
                'amount' => 100.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertCreated();
        $this->assertDatabaseHas('payment_transactions', [
            'checkout_session_id' => $session->id,
            'transaction_id' => 'ch_test123',
            'amount' => 100.00,
        ]);
    }

    public function test_customer_cannot_create_payment_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'checkout_session_id' => $session->id,
                'transactionId' => 'ch_test123',
                'paymentGateway' => 'stripe',
                'status' => 'pending',
                'amount' => 100.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_payment_transaction(): void
    {
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'checkout_session_id' => $session->id,
                'transactionId' => 'ch_test123',
                'status' => 'pending',
                'amount' => 100.00,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(401);
    }

    public function test_cannot_create_payment_transaction_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-transactions',
            'attributes' => [
                'status' => 'pending',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->post('/api/v1/payment-transactions');

        $response->assertStatus(422);
    }
}
