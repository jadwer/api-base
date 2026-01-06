<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;

class BankTransactionUpdateTest extends TestCase
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

    public function test_admin_can_update_bank_transaction(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create([
            'description' => 'Original description',
            'reference' => 'REF-001',
        ]);

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'description' => 'Updated description',
                'reference' => 'REF-002',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertOk();
        $this->assertDatabaseHas('bank_transactions', [
            'id' => $bankTransaction->id,
            'description' => 'Updated description',
            'reference' => 'REF-002',
        ]);
    }

    public function test_admin_can_update_transaction_amount(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create([
            'amount' => 1000.00,
        ]);

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'amount' => 2500.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertOk();
        $this->assertDatabaseHas('bank_transactions', [
            'id' => $bankTransaction->id,
            'amount' => 2500.00,
        ]);
    }

    public function test_admin_can_mark_transaction_as_reconciled(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create([
            'reconciliation_status' => 'unreconciled',
        ]);

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'reconciliationStatus' => 'reconciled',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertOk();
        $this->assertDatabaseHas('bank_transactions', [
            'id' => $bankTransaction->id,
            'reconciliation_status' => 'reconciled',
        ]);
    }

    public function test_update_validates_transaction_type(): void
    {
        $admin = $this->getAdminUser();
        $bankTransaction = BankTransaction::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'transactionType' => 'invalid_type',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_update_bank_transaction(): void
    {
        $bankTransaction = BankTransaction::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'description' => 'Updated description',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_update_bank_transaction(): void
    {
        $customer = $this->getCustomerUser();
        $bankTransaction = BankTransaction::factory()->create();

        $data = [
            'type' => 'bank-transactions',
            'id' => (string) $bankTransaction->id,
            'attributes' => [
                'description' => 'Updated description',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-transactions')
            ->withData($data)
            ->patch('/api/v1/bank-transactions/' . $bankTransaction->id);

        $response->assertForbidden();
    }
}
