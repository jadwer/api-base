<?php

namespace Modules\Reports\Tests\Feature\APAgingReports;

use Tests\TestCase;

class APAgingReportIndexTest extends TestCase
{

    public function test_admin_can_fetch_ap_aging_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'asOfDate',
                        'currency',
                        'agingBuckets',
                        'totals',
                        'generatedAt',
                    ],
                ],
            ],
        ]);
    }

    public function test_tech_user_can_fetch_ap_aging_reports()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertOk();
    }

    public function test_customer_cannot_fetch_ap_aging_reports()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_fetch_ap_aging_reports()
    {
        $response = $this->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertStatus(401);
    }

    public function test_can_filter_ap_aging_report_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.asOfDate', '2025-10-30');
    }

    public function test_ap_aging_report_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('agingBuckets', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertIsArray($data['agingBuckets']);
        $this->assertIsArray($data['totals']);
    }

    public function test_ap_aging_report_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->get('/api/v1/reports/ap-aging-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('totals', $data);
        $this->assertIsArray($data['totals']);
        $this->assertArrayHasKey('current', $data['totals']);
        $this->assertArrayHasKey('total', $data['totals']);
    }
}
