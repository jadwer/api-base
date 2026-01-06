<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;

class BankTransactionShowTest extends TestCase
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

    public function test_admin_can_show_bank_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get("/api/v1/bank-transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => (string) $transaction->id,
                'type' => 'bank-transactions',
                'attributes' => [
                    'bankAccountId' => $transaction->bank_account_id,
                    'amount' => $transaction->amount,
                    'transactionType' => $transaction->transaction_type,
                    'reconciliationStatus' => $transaction->reconciliation_status,
                ]
            ]
        ]);
    }

    public function test_admin_can_include_bank_account_relationship(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->includePaths('bankAccount')
            ->get("/api/v1/bank-transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'bankAccount' => ['data']
                ]
            ],
            'included'
        ]);
    }

    public function test_show_returns_404_for_non_existent_transaction(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get('/api/v1/bank-transactions/999999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_show_bank_transaction(): void
    {
        $bankAccount = BankAccount::factory()->create();
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
        ]);

        $response = $this->jsonApi()
            ->expects('bank-transactions')
            ->get("/api/v1/bank-transactions/{$transaction->id}");

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_show_bank_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $bankAccount = BankAccount::factory()->create();
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get("/api/v1/bank-transactions/{$transaction->id}");

        $response->assertForbidden();
    }
}
