<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyIndexTest extends TestCase
{



    public function test_admin_can_list_ExchangeRatePolicies(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRatePolicy::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'currency',
                        'source',
                        'scope',
                        'maxAgeDays',
                        'tolerancePercentage',
                        'requireApprovalOver',
                        'is_active',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ExchangeRatePolicies_by_currency(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRatePolicy::factory()->create(['currency' => 'test string']);
        ExchangeRatePolicy::factory()->create(['currency' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies?sort=currency');

        $response->assertOk();
    }

    public function test_admin_can_filter_ExchangeRatePolicies_by_isActive(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRatePolicy::factory()->create(['is_active' => true]);
        ExchangeRatePolicy::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies?filter[isActive]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ExchangeRatePolicies_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ExchangeRatePolicies(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ExchangeRatePolicies(): void
    {
        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ExchangeRatePolicies(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRatePolicy::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
