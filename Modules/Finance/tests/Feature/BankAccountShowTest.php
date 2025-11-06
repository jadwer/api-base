<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountShowTest extends TestCase
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

    public function test_admin_can_view_BankAccount(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'accountNumber',
                        'accountName',
                        'bankName',
                        'currency',
                        'glAccountId',
                        'currentBalance',
                        'openingBalance',
                        'status',
                        'metadata',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_BankAccount_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $bankAccount = BankAccount::factory()->create(['account_number' => 'test string', 'account_name' => 'Test Name', 'bank_name' => 'Test Name', 'currency' => 'test string', 'current_balance' => 99.99, 'opening_balance' => 99.99, 'status' => 'active', 'metadata' => 'test value', 'isActive' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'accountNumber',
                        'accountName',
                        'bankName',
                        'currency',
                        'glAccountId',
                        'currentBalance',
                        'openingBalance',
                        'status',
                        'metadata',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_BankAccount_with_permission(): void
    {
        $tech = $this->getTechUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_BankAccount(): void
    {
        $customer = $this->getCustomerUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_BankAccount(): void
    {
        $bankAccount = BankAccount::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_BankAccount(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
