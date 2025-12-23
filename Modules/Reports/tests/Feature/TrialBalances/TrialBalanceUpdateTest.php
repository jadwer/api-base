<?php

namespace Modules\Reports\Tests\Feature\TrialBalances;

use Tests\TestCase;

class TrialBalanceUpdateTest extends TestCase
{

    /** @test */
    public function cannot_update_balance_sheets()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'trial-balances',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->withData($data)
            ->patch('/api/v1/reports/trial-balances/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'trial-balances',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('trial-balances')
            ->withData($data)
            ->patch('/api/v1/reports/trial-balances/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
