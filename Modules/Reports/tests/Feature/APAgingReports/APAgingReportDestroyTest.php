<?php

namespace Modules\Reports\Tests\Feature\APAgingReports;

use Tests\TestCase;

class APAgingReportDestroyTest extends TestCase
{

    /** @test */
    public function cannot_delete_ap_aging_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->delete('/api/v1/reports/ap-aging-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_delete_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-aging-reports')
            ->delete('/api/v1/reports/ap-aging-reports/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
