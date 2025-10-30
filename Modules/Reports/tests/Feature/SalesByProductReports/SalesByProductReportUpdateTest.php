<?php

namespace Modules\Reports\Tests\Feature\SalesByProductReports;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesByProductReportUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function getAdminUser(): User
    {
        return User::role('admin')->first();
    }

    /** @test */
    public function cannot_update_sales_by_product_reports()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-by-product-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->withData($data)
            ->patch('/api/v1/reports/sales-by-product-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'sales-by-product-reports',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-by-product-reports')
            ->withData($data)
            ->patch('/api/v1/reports/sales-by-product-reports/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
