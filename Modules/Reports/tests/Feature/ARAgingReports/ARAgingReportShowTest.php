<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Tests\TestCase;

class ARAgingReportShowTest extends TestCase
{

    /** @test */
    public function admin_can_show_ar_aging_report()
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

    /** @test */
    public function tech_user_can_show_ar_aging_report()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_show_ar_aging_report()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_show_ar_aging_report()
    {
        $response = $this->jsonApi()
            ->expects('ar-aging-reports')
            ->get('/api/v1/reports/ar-aging-reports/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_ar_aging_report_with_date_filter()
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

    /** @test */
    public function ar_aging_report_show_includes_all_required_fields()
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
