<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceStoreTest extends TestCase
{



    public function test_admin_can_create_AccountBalance(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'fiscalYear' => 100,
                'fiscalMonth' => 100,
                'openingBalance' => 99.99,
                'periodDebits' => 99.99,
                'periodCredits' => 99.99,
                'closingBalance' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertCreated();
        
        $this->assertDatabaseHas('account_balances', ['fiscal_year' => 100, 'fiscal_month' => 100, 'opening_balance' => 99.99, 'period_debits' => 99.99, 'period_credits' => 99.99, 'closing_balance' => 99.99]);
    }

    public function test_admin_can_create_AccountBalance_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [

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
                'name' => 'Unauthorized AccountBalance',
                'is_active' => true
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
                'name' => 'Guest AccountBalance',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->post('/api/v1/account-balances');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_AccountBalance_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
