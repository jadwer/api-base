<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodIndexTest extends TestCase
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

    public function test_admin_can_list_FiscalPeriods(): void
    {
        $admin = $this->getAdminUser();
        
        FiscalPeriod::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'startDate',
                        'endDate',
                        'status',
                        'allowBackpost',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_FiscalPeriods_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        FiscalPeriod::factory()->create(['name' => 'Test Name']);
        FiscalPeriod::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_FiscalPeriods_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        FiscalPeriod::factory()->create(['status' => 'active']);
        FiscalPeriod::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_FiscalPeriods_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_FiscalPeriods(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_FiscalPeriods(): void
    {
        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods');

        $response->assertStatus(401);
    }

    public function test_can_paginate_FiscalPeriods(): void
    {
        $admin = $this->getAdminUser();
        
        FiscalPeriod::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
