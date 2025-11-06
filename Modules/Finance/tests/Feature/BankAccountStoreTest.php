<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountStoreTest extends TestCase
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

    public function test_admin_can_create_BankAccount(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'accountNumber' => 'test string',
                'accountName' => 'Test Name',
                'bankName' => 'Test Name',
                'currency' => 'test string',
                'glAccountId' => 1,
                'currentBalance' => 99.99,
                'openingBalance' => 99.99,
                'status' => 'active',
                'metadata' => ['key' => 'value'],
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('bank_accounts', ['account_number' => 'test string', 'account_name' => 'Test Name', 'bank_name' => 'Test Name', 'currency' => 'test string', 'gl_account_id' => 1, 'current_balance' => 99.99, 'opening_balance' => 99.99, 'status' => 'active', 'isActive' => true]);
    }

    public function test_admin_can_create_BankAccount_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'accountNumber' => 'MIN123',
                'accountName' => 'Minimal Account',
                'bankName' => 'Test Bank',
                'glAccountId' => 1,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_BankAccount(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'accountNumber' => '1234567890',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_BankAccount(): void
    {
        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'accountNumber' => '9876543210',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_BankAccount_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'accountNumber' => str_repeat('a', 300) // Exceeds max 255
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertStatus(422);
    }

    public function test_cannot_create_BankAccount_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-accounts',
            'attributes' => [
                'currentBalance' => 'not_numeric', // Invalid numeric
                'isActive' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->post('/api/v1/bank-accounts');

        $response->assertStatus(422);
    }
}
