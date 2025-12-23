<?php

namespace Modules\Reports\Tests\Feature\ARAgingReports;

use Tests\TestCase;

class ARAgingReportUpdateTest extends TestCase
{

    /** @test */
    public function cannot_update_ar_aging_reports()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-aging-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->withData($data)
            ->patch('/api/v1/reports/ar-aging-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-aging-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-aging-reports')
            ->withData($data)
            ->patch('/api/v1/reports/ar-aging-reports/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
