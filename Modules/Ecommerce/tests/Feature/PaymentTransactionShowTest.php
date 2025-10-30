<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;

class PaymentTransactionShowTest extends TestCase
{
    public function test_admin_can_view_payment_transaction(): void
    {
        $admin = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'payment-transactions',
                'id' => (string) $transaction->id,
            ]
        ]);
    }

    public function test_tech_user_can_view_payment_transaction(): void
    {
        $tech = $this->getTechUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertOk();
    }

    public function test_customer_can_view_own_payment_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create(['user_id' => $customer->id]);
        $transaction = PaymentTransaction::factory()->create(['checkout_session_id' => $session->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertOk();
    }

    public function test_guest_cannot_view_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_payment_transaction(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions/999999');

        $response->assertStatus(404);
    }
}
