<?php

namespace Modules\Reports\Tests\Feature\PurchaseBySupplierReports;

use Tests\TestCase;

class PurchaseBySupplierReportIndexTest extends TestCase
{

    public function test_admin_can_fetch_purchase_by_supplier_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'startDate',
                        'endDate',
                        'currency',
                        'purchasesBySupplier',
                        'summary',
                        'generatedAt',
                    ],
                ],
            ],
        ]);
    }

    public function test_tech_user_can_fetch_purchase_by_supplier_reports()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertOk();
    }

    public function test_customer_cannot_fetch_purchase_by_supplier_reports()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_fetch_purchase_by_supplier_reports()
    {
        $response = $this->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertStatus(401);
    }

    public function test_can_filter_purchase_by_supplier_report_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->filter(['startDate' => '2025-10-01', 'endDate' => '2025-10-30'])
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-01');
        $response->assertJsonPath('data.0.attributes.endDate', '2025-10-30');
    }

    public function test_purchase_by_supplier_report_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('purchasesBySupplier', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['purchasesBySupplier']);
        $this->assertIsArray($data['summary']);
    }

    public function test_purchase_by_supplier_report_includes_summary()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['summary']);
    }
}
