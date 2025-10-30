<?php

namespace Modules\Reports\Tests\Feature\CashFlows;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashFlowIndexTest extends TestCase
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
    public function admin_can_fetch_balance_sheets()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'startDate',
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
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_fetch_balance_sheets()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_fetch_balance_sheets()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_fetch_balance_sheets()
    {
        $response = $this->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_balance_sheet_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->filter(['startDate' => '2025-10-30'])
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-30');
    }

    /** @test */
    public function balance_sheet_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('assets', $data);
        $this->assertArrayHasKey('liabilities', $data);
        $this->assertArrayHasKey('equity', $data);
        $this->assertIsArray($data['assets']);
        $this->assertIsArray($data['liabilities']);
        $this->assertIsArray($data['equity']);
    }

    /** @test */
    public function balance_sheet_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->get('/api/v1/reports/cash-flows');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('totalAssets', $data);
        $this->assertArrayHasKey('totalLiabilities', $data);
        $this->assertArrayHasKey('totalEquity', $data);
        $this->assertIsNumeric($data['totalAssets']);
        $this->assertIsNumeric($data['totalLiabilities']);
        $this->assertIsNumeric($data['totalEquity']);
    }
}
