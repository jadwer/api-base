<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\PaymentTransaction;

class PaymentTransactionUpdateTest extends TestCase
{
    public function test_admin_can_update_payment_transaction(): void
    {
        $admin = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $data = [
            'type' => 'payment-transactions',
            'id' => (string) $transaction->id,
            'attributes' => [
                'status' => 'captured',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
            'status' => 'captured',
        ]);
    }

    public function test_customer_cannot_update_payment_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string) $transaction->id,
            'attributes' => [
                'status' => 'captured',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $data = [
            'type' => 'payment-transactions',
            'id' => (string) $transaction->id,
            'attributes' => [
                'status' => 'captured',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withData($data)
            ->patch('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);
    }
}
