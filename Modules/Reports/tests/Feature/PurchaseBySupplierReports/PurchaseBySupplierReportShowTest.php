<?php

namespace Modules\Reports\Tests\Feature\PurchaseBySupplierReports;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseBySupplierReportShowTest extends TestCase
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

    protected function getTechUser(): User
    {
        return User::role('tech')->first();
    }

    protected function getCustomerUser(): User
    {
        return User::role('customer')->first();
    }

    /** @test */
    public function admin_can_show_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'asOfDate',
                    'currency',
                    'balanced',
                    'assets',
                    'liabilities',
                    'equity',
                    'totalAssets',
                    'totalLiabilities',
                    'totalEquity',
                    'generatedAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_show_balance_sheet()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_show_balance_sheet()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_show_balance_sheet()
    {
        $response = $this->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_balance_sheet_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.asOfDate', '2025-10-30');
    }

    /** @test */
    public function balance_sheet_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-by-supplier-reports')
            ->get('/api/v1/reports/purchase-by-supplier-reports/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('asOfDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('balanced', $data);
        $this->assertArrayHasKey('assets', $data);
        $this->assertArrayHasKey('liabilities', $data);
        $this->assertArrayHasKey('equity', $data);
        $this->assertArrayHasKey('totalAssets', $data);
        $this->assertArrayHasKey('totalLiabilities', $data);
        $this->assertArrayHasKey('totalEquity', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
