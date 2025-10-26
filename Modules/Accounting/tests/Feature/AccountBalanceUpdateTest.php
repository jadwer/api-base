<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountBalance;
use Modules\Accounting\Models\Account;

class AccountBalanceUpdateTest extends TestCase
{
    public function test_admin_can_update_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'fiscalYear' => 2026,
                'fiscalMonth' => 6,
                'openingBalance' => 5000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();

        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id,
            'fiscal_year' => 2026,
            'fiscal_month' => 6,
            'opening_balance' => 5000.00
        ]);
    }

    public function test_admin_can_partially_update_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create([
            'fiscal_year' => 2025,
            'fiscal_month' => 3,
            'opening_balance' => 1000.00
        ]);

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'openingBalance' => 2000.00
                // fiscal_year and fiscal_month should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();

        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id,
            'fiscal_year' => 2025,
            'fiscal_month' => 3,
            'opening_balance' => 2000.00
        ]);
    }

    public function test_admin_can_update_AccountBalance_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();

        $accountBalance->refresh();
        $this->assertEquals($metadata, $accountBalance->metadata);
    }

    public function test_customer_user_cannot_update_AccountBalance(): void
    {
        $customer = $this->getCustomerUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'openingBalance' => 9999.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_AccountBalance(): void
    {
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'openingBalance' => 9999.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_AccountBalance(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'id' => '999999',
            'attributes' => [
                'openingBalance' => 1000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch('/api/v1/account-balances/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_AccountBalance_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'fiscalYear' => 'invalid_year', // Should be integer
                'openingBalance' => 'not_a_number' // Should be numeric
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(422);
    }
}
