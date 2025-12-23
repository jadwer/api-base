<?php

namespace Modules\Reports\Tests\Feature\APAgingReports;

use Tests\TestCase;

class APAgingReportUpdateTest extends TestCase
{

    /** @test */
    public function cannot_update_ap_aging_reports()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-aging-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->withData($data)
            ->patch('/api/v1/reports/ap-aging-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-aging-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->withData($data)
            ->patch('/api/v1/reports/ap-aging-reports/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
