<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Sales\Models\SalesOrder;

class PaymentTransactionShowTest extends TestCase
{
    /**
     * Test admin can view a payment transaction.
     */
    public function test_admin_can_view_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->captured()->create([
            'gateway' => 'stripe',
            'amount' => 1500.00,
            'currency' => 'MXN',
            'status' => 'captured',
            'payment_method' => 'card',
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJsonApiResource()
            ->assertJson([
                'data' => [
                    'type' => 'payment-transactions',
                    'id' => (string)$transaction->id,
                    'attributes' => [
                        'gateway' => 'stripe',
                        'amount' => 1500.00,
                        'currency' => 'MXN',
                        'status' => 'captured',
                        'paymentMethod' => 'card',
                    ],
                ],
            ]);
    }

    /**
     * Test tech can view a payment transaction.
     */
    public function test_tech_can_view_payment_transaction(): void
    {
        $user = $this->getTechUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJsonApiResource();
    }

    /**
     * Test customer can view a payment transaction.
     */
    public function test_customer_can_view_payment_transaction(): void
    {
        $user = $this->getCustomerUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJsonApiResource();
    }

    /**
     * Test guest cannot view a payment transaction.
     */
    public function test_guest_cannot_view_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);
    }

    /**
     * Test can view payment transaction with all attributes.
     */
    public function test_can_view_payment_transaction_with_all_attributes(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->captured()->create([
            'gateway' => 'stripe',
            'payment_intent_id' => 'pi_test_123456789',
            'transaction_id' => 'ch_test_987654321',
            'amount' => 2500.50,
            'currency' => 'USD',
            'status' => 'captured',
            'payment_method' => 'card',
            'card_brand' => 'visa',
            'card_last4' => '4242',
            'error_message' => null,
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'gateway' => 'stripe',
                        'paymentIntentId' => 'pi_test_123456789',
                        'transactionId' => 'ch_test_987654321',
                        'amount' => 2500.50,
                        'currency' => 'USD',
                        'status' => 'captured',
                        'paymentMethod' => 'card',
                        'cardBrand' => 'visa',
                        'cardLast4' => '4242',
                    ],
                ],
            ]);

        // Verify clientSecret is NOT exposed
        $response->assertJsonMissing(['clientSecret']);
    }

    /**
     * Test can include checkout session relationship.
     */
    public function test_can_include_checkout_session_relationship(): void
    {
        $user = $this->getAdminUser();
        $checkoutSession = CheckoutSession::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'checkout_session_id' => $checkoutSession->id,
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->includePaths('checkoutSession')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJsonApiResource()
            ->assertJsonApiIncluded([
                ['type' => 'checkout-sessions', 'id' => (string)$checkoutSession->id],
            ]);
    }

    /**
     * Test can include sales order relationship.
     */
    public function test_can_include_sales_order_relationship(): void
    {
        $user = $this->getAdminUser();
        $salesOrder = SalesOrder::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'sales_order_id' => $salesOrder->id,
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->includePaths('salesOrder')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJsonApiResource()
            ->assertJsonApiIncluded([
                ['type' => 'sales-orders', 'id' => (string)$salesOrder->id],
            ]);
    }

    /**
     * Test returns 404 for non-existent payment transaction.
     */
    public function test_returns_404_for_non_existent_payment_transaction(): void
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/99999');

        $response->assertStatus(404);
    }

    /**
     * Test can view failed payment transaction with error message.
     */
    public function test_can_view_failed_payment_transaction_with_error_message(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->failed()->create([
            'status' => 'failed',
            'error_message' => 'Your card was declined.',
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'failed',
                        'errorMessage' => 'Your card was declined.',
                    ],
                ],
            ]);
    }

    /**
     * Test can view refunded payment transaction.
     */
    public function test_can_view_refunded_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->refunded()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'refunded',
                    ],
                ],
            ]);

        // Verify refunded_at timestamp is present
        $this->assertNotNull($response->json('data.attributes.refundedAt'));
    }
}
