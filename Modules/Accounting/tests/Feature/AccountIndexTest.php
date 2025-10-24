<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Account;

class AccountIndexTest extends TestCase
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

    public function test_admin_can_list_Accounts(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'companyId',
                        'code',
                        'name',
                        'accountType',
                        'nature',
                        'level',
                        'parentId',
                        'currency',
                        'isPostable',
                        'isCashFlow',
                        'status',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Accounts_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->create(['name' => 'Test Name']);
        Account::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_Accounts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->create(['status' => 'active']);
        Account::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Accounts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Accounts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Accounts(): void
    {
        $response = $this->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Accounts(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
