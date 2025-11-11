<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;

class PaymentTransactionIndexTest extends TestCase
{
    /**
     * Test admin can list payment transactions.
     */
    public function test_admin_can_list_payment_transactions(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test tech can list payment transactions.
     */
    public function test_tech_can_list_payment_transactions(): void
    {
        $user = $this->getTechUser();
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test customer can list payment transactions.
     */
    public function test_customer_can_list_payment_transactions(): void
    {
        $user = $this->getCustomerUser();
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ;
    }

    /**
     * Test guest cannot list payment transactions.
     */
    public function test_guest_cannot_list_payment_transactions(): void
    {
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions');

        $response->assertStatus(401);
    }

    /**
     * Test can filter payment transactions by status.
     */
    public function test_can_filter_payment_transactions_by_status(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->captured()->count(2)->create();
        PaymentTransaction::factory()->pending()->create();
        PaymentTransaction::factory()->failed()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['status' => 'captured'])
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test can filter payment transactions by gateway.
     */
    public function test_can_filter_payment_transactions_by_gateway(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->stripe()->count(3)->create();
        PaymentTransaction::factory()->create(['gateway' => 'paypal']);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['gateway' => 'stripe'])
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can filter payment transactions by payment method.
     */
    public function test_can_filter_payment_transactions_by_payment_method(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->card()->count(2)->create();
        PaymentTransaction::factory()->oxxo()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['paymentMethod' => 'card'])
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test can filter payment transactions by currency.
     */
    public function test_can_filter_payment_transactions_by_currency(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->count(3)->create(['currency' => 'MXN']);
        PaymentTransaction::factory()->create(['currency' => 'USD']);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['currency' => 'MXN'])
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can sort payment transactions by amount.
     */
    public function test_can_sort_payment_transactions_by_amount(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->create(['amount' => 1000.00]);
        PaymentTransaction::factory()->create(['amount' => 500.00]);
        PaymentTransaction::factory()->create(['amount' => 2000.00]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('amount')
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ;

        $amounts = collect($response->json('data'))
            ->pluck('attributes.amount')
            ->toArray();

        $this->assertEquals([500.00, 1000.00, 2000.00], $amounts);
    }

    /**
     * Test can sort payment transactions by status.
     */
    public function test_can_sort_payment_transactions_by_status(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->create(['status' => 'pending']);
        PaymentTransaction::factory()->create(['status' => 'captured']);
        PaymentTransaction::factory()->create(['status' => 'failed']);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('status')
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ;
    }

    /**
     * Test can include checkout session relationship.
     */
    public function test_can_include_checkout_session_relationship(): void
    {
        $user = $this->getAdminUser();
        $checkoutSession = CheckoutSession::factory()->create();
        PaymentTransaction::factory()->create([
            'checkout_session_id' => $checkoutSession->id,
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->includePaths('checkoutSession')
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJsonApiIncluded([
                ['type' => 'checkout-sessions', 'id' => (string)$checkoutSession->id],
            ]);
    }

    /**
     * Test response has correct pagination meta.
     */
    public function test_response_has_correct_pagination_meta(): void
    {
        $user = $this->getAdminUser();
        PaymentTransaction::factory()->count(15)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->page(['number' => 1, 'size' => 10])
            ->get('/api/v1/payment-transactions');

        $response->assertSuccessful()
            ->assertJson([
                'meta' => [
                    'page' => [
                        'currentPage' => 1,
                        'from' => 1,
                        'lastPage' => 2,
                        'perPage' => 10,
                        'to' => 10,
                        'total' => 15,
                    ],
                ],
            ]);
    }
}
