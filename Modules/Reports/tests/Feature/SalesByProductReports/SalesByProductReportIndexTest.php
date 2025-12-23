<?php

namespace Modules\Reports\Tests\Feature\SalesByProductReports;

use Tests\TestCase;

class SalesByProductReportIndexTest extends TestCase
{

    /** @test */
    public function admin_can_fetch_sales_by_product_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

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
                        'salesByProduct',
                        'summary',
                        'generatedAt',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_fetch_sales_by_product_reports()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_fetch_sales_by_product_reports()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_fetch_sales_by_product_reports()
    {
        $response = $this->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_sales_by_product_report_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->filter(['startDate' => '2025-10-01', 'endDate' => '2025-10-30'])
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-01');
        $response->assertJsonPath('data.0.attributes.endDate', '2025-10-30');
    }

    /** @test */
    public function sales_by_product_report_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('salesByProduct', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['salesByProduct']);
        $this->assertIsArray($data['summary']);
    }

    /** @test */
    public function sales_by_product_report_includes_summary()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('summary', $data);
        $this->assertIsArray($data['summary']);
    }
}
