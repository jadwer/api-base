<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Tests\TestCase;

class ARAgingReportIndexTest extends TestCase
{

    /** @test */
    public function admin_can_fetch_ar_aging_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

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

    /** @test */
    public function tech_user_can_fetch_ar_aging_reports()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_fetch_ar_aging_reports()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_fetch_ar_aging_reports()
    {
        $response = $this->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_ar_aging_report_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.asOfDate', '2025-10-30');
    }

    /** @test */
    public function ar_aging_report_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('agingBuckets', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertIsArray($data['agingBuckets']);
        $this->assertIsArray($data['totals']);
    }

    /** @test */
    public function ar_aging_report_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('totals', $data);
        $this->assertIsArray($data['totals']);
        $this->assertArrayHasKey('current', $data['totals']);
        $this->assertArrayHasKey('total', $data['totals']);
    }
}
