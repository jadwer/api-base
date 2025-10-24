<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountIndexTest extends TestCase
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

    public function test_admin_can_list_BankAccounts(): void
    {
        $admin = $this->getAdminUser();
        
        BankAccount::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_BankAccounts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        BankAccount::factory()->create(['status' => 'active']);
        BankAccount::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_BankAccounts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        BankAccount::factory()->create(['status' => 'active']);
        BankAccount::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_BankAccounts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_BankAccounts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_BankAccounts(): void
    {
        $response = $this->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_BankAccounts(): void
    {
        $admin = $this->getAdminUser();
        
        BankAccount::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->get('/api/v1/bank-accounts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
