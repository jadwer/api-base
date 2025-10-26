<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountBalance;
use Modules\Accounting\Models\Account;

class AccountBalanceStoreTest extends TestCase
{
    public function test_admin_can_create_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'accountId' => $account->id,
                'fiscalYear' => 2025,
                'fiscalMonth' => 10,
                'openingBalance' => 1000.00,
                'periodDebits' => 500.00,
                'periodCredits' => 300.00,
                'closingBalance' => 1200.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertCreated();

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'fiscal_year' => 2025,
            'fiscal_month' => 10,
            'opening_balance' => 1000.00
        ]);
    }

    public function test_admin_can_create_AccountBalance_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'accountId' => $account->id,
                'fiscalYear' => 2025,
                'fiscalMonth' => 10
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_AccountBalance(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'fiscalYear' => 2025,
                'fiscalMonth' => 10
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_AccountBalance(): void
    {
        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'fiscalYear' => 2025,
                'fiscalMonth' => 10
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertStatus(401);
    }

    public function test_cannot_create_AccountBalance_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'openingBalance' => 1000.00
                // Missing fiscalYear, fiscalMonth
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertStatus(422);
    }

    public function test_cannot_create_AccountBalance_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'fiscalYear' => 'invalid', // Should be integer
                'fiscalMonth' => 'invalid' // Should be integer
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertStatus(422);
    }
}
