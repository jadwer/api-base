<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceShowTest extends TestCase
{



    public function test_admin_can_view_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'accountId',
                        'fiscalYear',
                        'fiscalMonth',
                        'openingBalance',
                        'periodDebits',
                        'periodCredits',
                        'closingBalance',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_AccountBalance_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $accountBalance = AccountBalance::factory()->create(['fiscal_year' => 100, 'fiscal_month' => 100, 'opening_balance' => 99.99, 'period_debits' => 99.99, 'period_credits' => 99.99, 'closing_balance' => 99.99]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'accountId',
                        'fiscalYear',
                        'fiscalMonth',
                        'openingBalance',
                        'periodDebits',
                        'periodCredits',
                        'closingBalance',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_AccountBalance_with_permission(): void
    {
        $tech = $this->getTechUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_AccountBalance(): void
    {
        $customer = $this->getCustomerUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_AccountBalance(): void
    {
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_AccountBalance(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
