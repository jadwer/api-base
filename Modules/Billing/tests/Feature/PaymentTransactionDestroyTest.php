<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\PaymentTransaction;

class PaymentTransactionDestroyTest extends TestCase
{
    /**
     * Test admin can delete a payment transaction.
     */
    public function test_admin_can_delete_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('payment_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /**
     * Test tech cannot delete a payment transaction.
     */
    public function test_tech_cannot_delete_payment_transaction(): void
    {
        $user = $this->getTechUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /**
     * Test customer cannot delete a payment transaction.
     */
    public function test_customer_cannot_delete_payment_transaction(): void
    {
        $user = $this->getCustomerUser();
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /**
     * Test guest cannot delete a payment transaction.
     */
    public function test_guest_cannot_delete_payment_transaction(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /**
     * Test can delete pending payment transaction.
     */
    public function test_can_delete_pending_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->pending()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();
    }

    /**
     * Test can delete captured payment transaction.
     */
    public function test_can_delete_captured_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->captured()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();
    }

    /**
     * Test can delete failed payment transaction.
     */
    public function test_can_delete_failed_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->failed()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();
    }

    /**
     * Test can delete refunded payment transaction.
     */
    public function test_can_delete_refunded_payment_transaction(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->refunded()->create();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();
    }

    /**
     * Test returns 404 when deleting non-existent payment transaction.
     */
    public function test_returns_404_when_deleting_non_existent_payment_transaction(): void
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/99999');

        $response->assertStatus(404);
    }

    /**
     * Test can delete payment transaction with relationships.
     */
    public function test_can_delete_payment_transaction_with_relationships(): void
    {
        $user = $this->getAdminUser();
        $checkoutSession = \Modules\Ecommerce\Models\CheckoutSession::factory()->create();
        $salesOrder = \Modules\Sales\Models\SalesOrder::factory()->create();

        $transaction = PaymentTransaction::factory()->create([
            'checkout_session_id' => $checkoutSession->id,
            'sales_order_id' => $salesOrder->id,
        ]);

        $response = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction->id);

        $response->assertNoContent();

        // Verify related entities still exist (SET NULL behavior)
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $checkoutSession->id,
        ]);
        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
        ]);
    }

    /**
     * Test delete operation is idempotent.
     */
    public function test_delete_operation_is_idempotent(): void
    {
        $user = $this->getAdminUser();
        $transaction = PaymentTransaction::factory()->create();
        $transactionId = $transaction->id;

        // First delete
        $response1 = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transactionId);

        $response1->assertNoContent();

        // Second delete on same ID
        $response2 = $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transactionId);

        $response2->assertStatus(404);
    }

    /**
     * Test can delete multiple payment transactions.
     */
    public function test_can_delete_multiple_payment_transactions(): void
    {
        $user = $this->getAdminUser();
        $transaction1 = PaymentTransaction::factory()->create();
        $transaction2 = PaymentTransaction::factory()->create();
        $transaction3 = PaymentTransaction::factory()->create();

        // Delete first
        $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction1->id)
            ->assertNoContent();

        // Delete second
        $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction2->id)
            ->assertNoContent();

        // Delete third
        $this->jsonApi()
            ->expects('payment-transactions')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/payment-transactions/' . $transaction3->id)
            ->assertNoContent();

        // Verify all deleted
        $this->assertDatabaseMissing('payment_transactions', ['id' => $transaction1->id]);
        $this->assertDatabaseMissing('payment_transactions', ['id' => $transaction2->id]);
        $this->assertDatabaseMissing('payment_transactions', ['id' => $transaction3->id]);
    }
}
