<?php

namespace Modules\Reports\Tests\Feature\BalanceSheets;

use Tests\TestCase;

class BalanceSheetShowTest extends TestCase
{

    public function test_admin_can_show_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->get('/api/v1/reports/balance-sheets/1');

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

    public function test_tech_user_can_show_balance_sheet()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->get('/api/v1/reports/balance-sheets/1');

        $response->assertOk();
    }

    public function test_customer_cannot_show_balance_sheet()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->get('/api/v1/reports/balance-sheets/1');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_show_balance_sheet()
    {
        $response = $this->jsonApi()
            ->expects('balance-sheets')
            ->get('/api/v1/reports/balance-sheets/1');

        $response->assertStatus(401);
    }

    public function test_can_show_balance_sheet_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/balance-sheets/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.asOfDate', '2025-10-30');
    }

    public function test_balance_sheet_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->get('/api/v1/reports/balance-sheets/1');

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
