<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;

class BankTransactionStoreTest extends TestCase
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

    public function test_admin_can_create_bank_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'bankAccountId' => $bankAccount->id,
                'transactionDate' => now()->toDateString(),
                'amount' => 1500.00,
                'transactionType' => 'debit',
                'reference' => 'DEP-001',
                'description' => 'Customer payment deposit',
                'reconciliationStatus' => 'unreconciled',
                'isActive' => true,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertCreated();
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'amount' => 1500.00,
            'transaction_type' => 'debit',
            'reference' => 'DEP-001',
        ]);
    }

    public function test_admin_can_create_credit_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'bankAccountId' => $bankAccount->id,
                'transactionDate' => now()->toDateString(),
                'amount' => -2500.00,
                'transactionType' => 'credit',
                'reference' => 'WD-001',
                'description' => 'Vendor payment',
                'reconciliationStatus' => 'unreconciled',
                'isActive' => true,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertCreated();
        $this->assertDatabaseHas('bank_transactions', [
            'transaction_type' => 'credit',
        ]);
    }

    public function test_store_requires_bank_account_id(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'transactionDate' => now()->toDateString(),
                'amount' => 1500.00,
                'transactionType' => 'debit',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertUnprocessable();
    }

    public function test_store_validates_transaction_type(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'bankAccountId' => $bankAccount->id,
                'transactionDate' => now()->toDateString(),
                'amount' => 1500.00,
                'transactionType' => 'invalid',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_create_bank_transaction(): void
    {
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'bankAccountId' => $bankAccount->id,
                'transactionDate' => now()->toDateString(),
                'amount' => 1500.00,
                'transactionType' => 'debit',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_bank_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'attributes' => [
                'bankAccountId' => $bankAccount->id,
                'transactionDate' => now()->toDateString(),
                'amount' => 1500.00,
                'transactionType' => 'debit',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->post('/api/v1/bank-transactions');

        $response->assertForbidden();
    }
}
