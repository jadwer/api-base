<?php

namespace Modules\Reports\Tests\Feature\CashFlows;

use Tests\TestCase;

class CashFlowShowTest extends TestCase
{

    /** @test */
    public function admin_can_show_cash_flow()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'startDate',
                    'endDate',
                    'currency',
                    'beginningCash',
                    'operatingActivities',
                    'investingActivities',
                    'financingActivities',
                    'netCashFlow',
                    'endingCash',
                    'generatedAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_show_cash_flow()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_show_cash_flow()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_show_cash_flow()
    {
        $response = $this->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_cash_flow_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->filter(['startDate' => '2025-10-01'])
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.startDate', '2025-10-01');
    }

    /** @test */
    public function cash_flow_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('startDate', $data);
        $this->assertArrayHasKey('endDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('beginningCash', $data);
        $this->assertArrayHasKey('operatingActivities', $data);
        $this->assertArrayHasKey('investingActivities', $data);
        $this->assertArrayHasKey('financingActivities', $data);
        $this->assertArrayHasKey('netCashFlow', $data);
        $this->assertArrayHasKey('endingCash', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
