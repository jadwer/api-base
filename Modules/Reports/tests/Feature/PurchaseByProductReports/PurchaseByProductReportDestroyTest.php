<?php

namespace Modules\Reports\Tests\Feature\PurchaseByProductReports;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseByProductReportDestroyTest extends TestCase
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
    public function cannot_delete_purchase_by_product_reports()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->delete('/api/v1/reports/purchase-by-product-reports/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_delete_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-product-reports')
            ->delete('/api/v1/reports/purchase-by-product-reports/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
