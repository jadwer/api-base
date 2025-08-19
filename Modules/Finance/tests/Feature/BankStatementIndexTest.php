<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatement;

class BankStatementIndexTest extends TestCase
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

    public function test_admin_can_list_BankStatements(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatement::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'bankAccountId',
                        'statementDate',
                        'importSource',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_BankStatements_by_importSource(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatement::factory()->create(['import_source' => 'test string']);
        BankStatement::factory()->create(['import_source' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements?sort=importSource');

        $response->assertOk();
    }

    public function test_admin_can_filter_BankStatements_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatement::factory()->create([]);
        BankStatement::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_BankStatements_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_BankStatements(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_BankStatements(): void
    {
        $response = $this->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements');

        $response->assertStatus(401);
    }

    public function test_can_paginate_BankStatements(): void
    {
        $admin = $this->getAdminUser();
        
        BankStatement::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
