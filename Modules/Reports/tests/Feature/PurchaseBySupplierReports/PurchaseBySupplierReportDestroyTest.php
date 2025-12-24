<?php

namespace Modules\Reports\Tests\Feature\PurchaseBySupplierReports;

use Tests\TestCase;

class PurchaseBySupplierReportDestroyTest extends TestCase
{

    public function test_cannot_delete_purchase_by_supplier_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->delete('/api/v1/reports/purchase-by-supplier-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }

    public function test_admin_cannot_delete_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->delete('/api/v1/reports/purchase-by-supplier-reports/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
