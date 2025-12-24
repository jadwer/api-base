<?php

namespace Modules\Reports\Tests\Feature\TrialBalances;

use Tests\TestCase;

class TrialBalanceIndexTest extends TestCase
{

    public function test_admin_can_fetch_trial_balances()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'asOfDate',
                        'currency',
                        'accounts',
                        'totals',
                        'summaryByType',
                        'balanced',
                        'generatedAt',
                    ],
                ],
            ],
        ]);
    }

    public function test_tech_user_can_fetch_trial_balances()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertOk();
    }

    public function test_customer_cannot_fetch_trial_balances()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_fetch_trial_balances()
    {
        $response = $this->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertStatus(401);
    }

    public function test_can_filter_trial_balance_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/trial-balances');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.asOfDate', '2025-10-30');
    }

    public function test_trial_balance_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('accounts', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('summaryByType', $data);
        $this->assertIsArray($data['accounts']);
        $this->assertIsArray($data['totals']);
        $this->assertIsArray($data['summaryByType']);
    }

    public function test_trial_balance_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('balanced', $data);
        $this->assertIsArray($data['totals']);
        $this->assertIsBool($data['balanced']);
    }
}
