<?php

namespace Modules\Reports\Tests\Feature\PurchaseByProductReports;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseByProductReportStoreTest extends TestCase
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
    public function cannot_create_purchase_by_product_reports_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'purchase-by-product-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->withData($data)
            ->post('/api/v1/reports/purchase-by-product-reports');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'purchase-by-product-reports',
            'attributes' => [
                'asOfDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->withData($data)
            ->post('/api/v1/reports/purchase-by-product-reports');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
