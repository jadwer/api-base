<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineIndexTest extends TestCase
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

    public function test_admin_can_list_BankStatementLines(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatementLine::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'bankStatementId',
                        'txnDate',
                        'amount',
                        'counterparty',
                        'reference',
                        'fitid',
                        'status',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_BankStatementLines_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatementLine::factory()->create(['status' => 'active']);
        BankStatementLine::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_BankStatementLines_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatementLine::factory()->create(['status' => 'active']);
        BankStatementLine::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_BankStatementLines_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_BankStatementLines(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_BankStatementLines(): void
    {
        $response = $this->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines');

        $response->assertStatus(401);
    }

    public function test_can_paginate_BankStatementLines(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatementLine::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
