<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodShowTest extends TestCase
{
    public function test_admin_can_view_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'name' => 'Test Period',
            'status' => 'draft',
            'period_type' => 'monthly',
            'total_gross' => 10000.00,
            'total_deductions' => 1000.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'periodType',
                    'startDate',
                    'endDate',
                    'paymentDate',
                    'status',
                    'totalGross',
                    'totalDeductions',
                    'totalNet',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);

        $this->assertEquals('Test Period', $response->json('data.attributes.name'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        $this->assertEquals('monthly', $response->json('data.attributes.periodType'));
        $this->assertEquals(10000.00, $response->json('data.attributes.totalGross'));
        $this->assertEquals(1000.00, $response->json('data.attributes.totalDeductions'));
        $this->assertEquals(9000.00, $response->json('data.attributes.totalNet'));
    }

    public function test_tech_user_can_view_payroll_period(): void
    {
        $tech = $this->getTechUser();

        $period = PayrollPeriod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals($period->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_payroll_period(): void
    {
        $customer = $this->getCustomerUser();

        $period = PayrollPeriod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_payroll_period(): void
    {
        $period = PayrollPeriod::factory()->create();

        $response = $this->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get('/api/v1/payroll-periods/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_payroll_items(): void
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

    public function test_total_net_is_calculated_correctly(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'total_gross' => 15000.00,
            'total_deductions' => 2500.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals(12500.00, $response->json('data.attributes.totalNet'));
    }

    public function test_can_view_paid_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->paid()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('paid', $response->json('data.attributes.status'));
    }

    public function test_can_view_closed_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->closed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->get("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('closed', $response->json('data.attributes.status'));
    }
}
