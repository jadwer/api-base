<?php

namespace Modules\Reports\Tests\Feature\SalesByProductReports;

use Tests\TestCase;

class SalesByProductReportShowTest extends TestCase
{

    public function test_admin_can_show_sales_by_product_report()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
        ]);
    }

    public function test_tech_user_can_show_sales_by_product_report()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertOk();
    }

    public function test_customer_cannot_show_sales_by_product_report()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_show_sales_by_product_report()
    {
        $response = $this->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertStatus(401);
    }

    public function test_can_show_sales_by_product_report_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->filter(['startDate' => '2025-10-01', 'endDate' => '2025-10-30'])
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.startDate', '2025-10-01');
        $response->assertJsonPath('data.attributes.endDate', '2025-10-30');
    }

    public function test_sales_by_product_report_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->get('/api/v1/reports/sales-by-product-reports/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('startDate', $data);
        $this->assertArrayHasKey('endDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('salesByProduct', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
