<?php

namespace Modules\Reports\Tests\Feature\SalesByProductReports;

use Tests\TestCase;

class SalesByProductReportDestroyTest extends TestCase
{

    public function test_cannot_delete_sales_by_product_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->delete('/api/v1/reports/sales-by-product-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }

    public function test_admin_cannot_delete_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->delete('/api/v1/reports/sales-by-product-reports/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
