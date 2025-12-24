<?php

namespace Modules\Reports\Tests\Feature\PurchaseBySupplierReports;

use Tests\TestCase;

class PurchaseBySupplierReportStoreTest extends TestCase
{

    public function test_cannot_create_purchase_by_supplier_reports_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'purchase-by-supplier-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->withData($data)
            ->post('/api/v1/reports/purchase-by-supplier-reports');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    public function test_admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'purchase-by-supplier-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->withData($data)
            ->post('/api/v1/reports/purchase-by-supplier-reports');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
