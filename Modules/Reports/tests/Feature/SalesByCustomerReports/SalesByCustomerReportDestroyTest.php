<?php

namespace Modules\Reports\Tests\Feature\SalesByCustomerReports;

use Tests\TestCase;

class SalesByCustomerReportDestroyTest extends TestCase
{

    /** @test */
    public function cannot_delete_sales_by_customer_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-customer-reports')
            ->delete('/api/v1/reports/sales-by-customer-reports/1');

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
            ->expects('sales-by-customer-reports')
            ->delete('/api/v1/reports/sales-by-customer-reports/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
