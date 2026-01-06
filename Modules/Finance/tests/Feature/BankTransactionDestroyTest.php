<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;

class BankTransactionDestroyTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_delete_bank_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->delete('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('bank_transactions', [
            'id' => $bankTransaction->id,
        ]);
    }

    public function test_admin_can_delete_unreconciled_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create([
            'reconciliation_status' => 'unreconciled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->delete('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('bank_transactions', [
            'id' => $bankTransaction->id,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_transaction(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->delete('/api/v1/bank-transactions/99999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_delete_bank_transaction(): void
    {
        $bankTransaction = BankTransaction::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-transactions')
            ->delete('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_delete_bank_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $bankTransaction = BankTransaction::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->delete('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertForbidden();
    }
}
