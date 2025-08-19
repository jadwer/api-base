<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountShowTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
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
                        'bankName',
                        'accountNumber',
                        'clabe',
                        'currency',
                        'accountType',
                        'openingBalance',
                        'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_BankAccount_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $bankAccount = BankAccount::factory()->create(['bank_name' => 'Test Name', 'account_number' => 'test string', 'clabe' => 'test string', 'currency' => 'test string', 'account_type' => 'test string', 'opening_balance' => 99.99, 'status' => 'active']);

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
                        'bankName',
                        'accountNumber',
                        'clabe',
                        'currency',
                        'accountType',
                        'openingBalance',
                        'status',
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
