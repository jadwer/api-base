<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodIndexTest extends TestCase
{
    public function test_admin_can_list_payroll_periods(): void
    {
        $admin = $this->getAdminUser();

        PayrollPeriod::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_payroll_periods_by_name(): void
    {
        $admin = $this->getAdminUser();

        PayrollPeriod::factory()->create([
            'name' => 'Periodo ZZZ',
            'period_type' => 'monthly',
        ]);
        PayrollPeriod::factory()->create([
            'name' => 'Periodo AAA',
            'period_type' => 'monthly',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods?sort=name&page[size]=100');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data), 'Should have at least one payroll period');

        // Find our test periods in the response
        $testPeriods = collect($data)->filter(function($item) {
            $name = $item['attributes']['name'] ?? null;
            return $name === 'Periodo AAA' || $name === 'Periodo ZZZ';
        })->values();

        $this->assertCount(2, $testPeriods, 'Should find both test periods');
        $this->assertEquals('Periodo AAA', $testPeriods[0]['attributes']['name'], 'First should be AAA (sorted ascending)');
        $this->assertEquals('Periodo ZZZ', $testPeriods[1]['attributes']['name'], 'Second should be ZZZ');
    }

    public function test_admin_can_filter_payroll_periods_by_status(): void
    {
        $admin = $this->getAdminUser();

        PayrollPeriod::factory()->count(2)->create([
            'status' => 'paid'
        ]);
        PayrollPeriod::factory()->count(1)->create([
            'status' => 'draft'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods?filter[status]=paid');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_payroll_periods_by_period_type(): void
    {
        $admin = $this->getAdminUser();

        PayrollPeriod::factory()->count(2)->create([
            'period_type' => 'monthly'
        ]);
        PayrollPeriod::factory()->count(1)->create([
            'period_type' => 'weekly'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods?filter[periodType]=monthly');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_payroll_periods_with_permission(): void
    {
        $tech = $this->getTechUser();

        PayrollPeriod::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_payroll_periods(): void
    {
        $customer = $this->getCustomerUser();

        PayrollPeriod::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_payroll_periods(): void
    {
        $response = $this->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods');

        $response->assertStatus(401);
    }

    public function test_can_paginate_payroll_periods(): void
    {
        $admin = $this->getAdminUser();

        PayrollPeriod::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');

        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);

        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_payroll_items_relationship(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}?include=payrollItems");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'payrollItems'
                ]
            ]
        ]);
    }
}
