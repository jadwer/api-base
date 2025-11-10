<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceIndexTest extends TestCase
{



    public function test_admin_can_list_AccountBalances(): void
    {
        $admin = $this->getAdminUser();
        
        AccountBalance::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'accountId',
                        'fiscalYear',
                        'fiscalMonth',
                        'openingBalance',
                        'periodDebits',
                        'periodCredits',
                        'closingBalance',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_AccountBalances_by_createdAt(): void
    {
        $admin = $this->getAdminUser();
        
        AccountBalance::factory()->create([]);
        AccountBalance::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances?sort=createdAt');

        $response->assertOk();
    }

    public function test_admin_can_filter_AccountBalances_by_fiscal_year(): void
    {
        $admin = $this->getAdminUser();

        AccountBalance::factory()->create(['fiscal_year' => 2024]);
        AccountBalance::factory()->create(['fiscal_year' => 2025]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances?filter[fiscal_year]=2025');

        $response->assertOk();
    }

    public function test_tech_user_can_list_AccountBalances_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_AccountBalances(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_AccountBalances(): void
    {
        $response = $this->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances');

        $response->assertStatus(401);
    }

    public function test_can_paginate_AccountBalances(): void
    {
        $admin = $this->getAdminUser();
        
        AccountBalance::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->get('/api/v1/account-balances?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
