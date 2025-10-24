<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateIndexTest extends TestCase
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

    public function test_admin_can_list_ExchangeRates(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRate::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'fromCurrency',
                        'toCurrency',
                        'rate',
                        'effectiveDate',
                        'source',
                        'status',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ExchangeRates_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRate::factory()->create(['status' => 'active']);
        ExchangeRate::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_ExchangeRates_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRate::factory()->create(['status' => 'active']);
        ExchangeRate::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ExchangeRates_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ExchangeRates(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ExchangeRates(): void
    {
        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ExchangeRates(): void
    {
        $admin = $this->getAdminUser();
        
        ExchangeRate::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
