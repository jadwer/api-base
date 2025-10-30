<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\PaymentTransaction;

class PaymentTransactionDestroyTest extends TestCase
{
    public function test_admin_can_delete_payment_transaction(): void
    {
        $admin = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('payment_transactions', ['id' => $transaction->id]);
    }

    public function test_customer_cannot_delete_payment_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id]);
    }

    public function test_guest_cannot_delete_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_payment_transaction(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-transactions')
            ->delete('/api/v1/payment-transactions/999999');

        $response->assertStatus(404);
    }
}
