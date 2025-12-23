<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\PaymentTransaction;

class PaymentTransactionIndexTest extends TestCase
{
    public function test_admin_can_list_payment_transactions(): void
    {
        $admin = $this->getAdminUser();
        PaymentTransaction::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'transactionId',
                        'status',
                        'amount',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_payment_transactions_by_status(): void
    {
        $admin = $this->getAdminUser();
        PaymentTransaction::factory()->create(['status' => 'captured']);
        PaymentTransaction::factory()->create(['status' => 'failed']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions?filter[status]=captured');

        $response->assertOk();
    }

    public function test_tech_user_can_list_payment_transactions(): void
    {
        $tech = $this->getTechUser();
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions');

        $response->assertOk();
    }

    public function test_customer_can_list_payment_transactions(): void
    {
        $customer = $this->getCustomerUser();
        // Customer has read-only access to payment transactions per seeder
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions');

        $response->assertOk();
    }

    public function test_guest_cannot_list_payment_transactions(): void
    {
        PaymentTransaction::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->get('/api/v1/payment-transactions');

        $response->assertStatus(401);
    }
}
