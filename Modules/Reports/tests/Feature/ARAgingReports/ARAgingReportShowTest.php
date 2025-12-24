<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Tests\TestCase;

class ARAgingReportShowTest extends TestCase
{

    public function test_admin_can_show_ar_aging_report()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
        ]);
    }

    public function test_tech_user_can_show_ar_aging_report()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertOk();
    }

    public function test_customer_cannot_show_ar_aging_report()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_show_ar_aging_report()
    {
        $response = $this->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertStatus(401);
    }

    public function test_can_show_ar_aging_report_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.asOfDate', '2025-10-30');
    }

    public function test_ar_aging_report_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('asOfDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('agingBuckets', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
