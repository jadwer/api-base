<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;

class BankTransactionIndexTest extends TestCase
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

    public function test_admin_can_list_bank_transactions(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        BankTransaction::factory()->count(3)->create([
            'bank_account_id' => $bankAccount->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get('/api/v1/bank-transactions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'bankAccountId',
                        'transactionDate',
                        'amount',
                        'transactionType',
                        'reconciliationStatus',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_bank_transactions_by_reconciliation_status(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'reconciliation_status' => 'unreconciled',
        ]);
        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'reconciliation_status' => 'reconciled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->filter(['reconciliation_status' => 'unreconciled'])
            ->get('/api/v1/bank-transactions');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_filter_bank_transactions_by_bank_account(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount1 = BankAccount::factory()->create();
        $bankAccount2 = BankAccount::factory()->create();

        BankTransaction::factory()->count(2)->create([
            'bank_account_id' => $bankAccount1->id,
        ]);
        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount2->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->filter(['bank_account_id' => $bankAccount1->id])
            ->get('/api/v1/bank-transactions');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_sort_bank_transactions_by_transaction_date(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now()->subDays(2),
        ]);
        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get('/api/v1/bank-transactions?sort=-transactionDate');

        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_list_bank_transactions(): void
    {
        $response = $this->jsonApi()
            ->expects('bank-transactions')
            ->get('/api/v1/bank-transactions');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_list_bank_transactions(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->get('/api/v1/bank-transactions');

        $response->assertForbidden();
    }
}
