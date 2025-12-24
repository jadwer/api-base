<?php

namespace Modules\Reports\Tests\Feature\PurchaseByProductReports;

use Tests\TestCase;

class PurchaseByProductReportIndexTest extends TestCase
{

    public function test_admin_can_fetch_purchase_by_product_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

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
                        'purchasesByProduct',
                        'summary',
                        'generatedAt',
                    ],
                ],
            ],
        ]);
    }

    public function test_tech_user_can_fetch_purchase_by_product_reports()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertOk();
    }

    public function test_customer_cannot_fetch_purchase_by_product_reports()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_fetch_purchase_by_product_reports()
    {
        $response = $this->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertStatus(401);
    }

    public function test_can_filter_purchase_by_product_report_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->filter(['startDate' => '2025-10-01', 'endDate' => '2025-10-30'])
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-01');
        $response->assertJsonPath('data.0.attributes.endDate', '2025-10-30');
    }

    public function test_purchase_by_product_report_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('purchasesByProduct', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['purchasesByProduct']);
        $this->assertIsArray($data['summary']);
    }

    public function test_purchase_by_product_report_includes_summary()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->get('/api/v1/reports/purchase-by-product-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['summary']);
    }
}
