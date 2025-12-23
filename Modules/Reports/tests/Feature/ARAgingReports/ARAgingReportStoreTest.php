<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Tests\TestCase;

class ARAgingReportStoreTest extends TestCase
{

    /** @test */
    public function cannot_create_ar_aging_reports_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-aging-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->withData($data)
            ->post('/api/v1/reports/ar-aging-reports');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-aging-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->withData($data)
            ->post('/api/v1/reports/ar-aging-reports');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
