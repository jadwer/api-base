<?php

namespace Modules\Reports\Tests\Feature\CashFlows;

use Tests\TestCase;

class CashFlowIndexTest extends TestCase
{

    public function test_admin_can_fetch_cash_flows()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
            ],
        ]);
    }

    public function test_tech_user_can_fetch_cash_flows()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
    }

    public function test_customer_cannot_fetch_cash_flows()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_fetch_cash_flows()
    {
        $response = $this->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertStatus(401);
    }

    public function test_can_filter_cash_flow_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->filter(['startDate' => '2025-10-01'])
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-01');
    }

    public function test_cash_flow_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('operatingActivities', $data);
        $this->assertArrayHasKey('investingActivities', $data);
        $this->assertArrayHasKey('financingActivities', $data);
        $this->assertIsNumeric($data['operatingActivities']);
        $this->assertIsNumeric($data['investingActivities']);
        $this->assertIsNumeric($data['financingActivities']);
    }

    public function test_cash_flow_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('beginningCash', $data);
        $this->assertArrayHasKey('netCashFlow', $data);
        $this->assertArrayHasKey('endingCash', $data);
        $this->assertIsNumeric($data['beginningCash']);
        $this->assertIsNumeric($data['netCashFlow']);
        $this->assertIsNumeric($data['endingCash']);
    }
}
