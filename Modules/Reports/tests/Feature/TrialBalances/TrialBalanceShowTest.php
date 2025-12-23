<?php

namespace Modules\Reports\Tests\Feature\TrialBalances;

use Tests\TestCase;

class TrialBalanceShowTest extends TestCase
{

    /** @test */
    public function admin_can_show_trial_balance()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
        ]);
    }

    /** @test */
    public function tech_user_can_show_trial_balance()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_show_trial_balance()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_show_trial_balance()
    {
        $response = $this->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_trial_balance_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->filter(['asOfDate' => '2025-10-30'])
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.asOfDate', '2025-10-30');
    }

    /** @test */
    public function trial_balance_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->get('/api/v1/reports/trial-balances/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('asOfDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('accounts', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('summaryByType', $data);
        $this->assertArrayHasKey('balanced', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
