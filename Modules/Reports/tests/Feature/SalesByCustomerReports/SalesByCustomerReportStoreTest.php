<?php

namespace Modules\Reports\Tests\Feature\SalesByCustomerReports;

use Tests\TestCase;

class SalesByCustomerReportStoreTest extends TestCase
{

    /** @test */
    public function cannot_create_sales_by_customer_reports_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-by-customer-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-customer-reports')
            ->withData($data)
            ->post('/api/v1/reports/sales-by-customer-reports');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-by-customer-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-customer-reports')
            ->withData($data)
            ->post('/api/v1/reports/sales-by-customer-reports');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
