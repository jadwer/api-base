<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingIndexTest extends TestCase
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

    public function test_admin_can_list_AccountMappings(): void
    {
        $admin = $this->getAdminUser();
        
        AccountMapping::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'companyId',
                        'mappingType',
                        'accountId',
                        'version',
                        'effectiveFrom',
                        'effectiveTo',
                        'isActive',
                        'createdById',
                        'notes',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_AccountMappings_by_mappingType(): void
    {
        $admin = $this->getAdminUser();
        
        AccountMapping::factory()->create(['mapping_type' => 'test string']);
        AccountMapping::factory()->create(['mapping_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings?sort=mappingType');

        $response->assertOk();
    }

    public function test_admin_can_filter_AccountMappings_by_isActive(): void
    {
        $admin = $this->getAdminUser();
        
        AccountMapping::factory()->create(['is_active' => true]);
        AccountMapping::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings?filter[isActive]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_AccountMappings_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_AccountMappings(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_AccountMappings(): void
    {
        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings');

        $response->assertStatus(401);
    }

    public function test_can_paginate_AccountMappings(): void
    {
        $admin = $this->getAdminUser();
        
        AccountMapping::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
