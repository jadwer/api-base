<?php

namespace Modules\Reports\Tests\Feature\CashFlows;

use Tests\TestCase;

class CashFlowStoreTest extends TestCase
{

    /** @test */
    public function cannot_create_balance_sheets_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cash-flows',
            'attributes' => [
                'startDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->withData($data)
            ->post('/api/v1/reports/cash-flows');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cash-flows',
            'attributes' => [
                'startDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cash-flows')
            ->withData($data)
            ->post('/api/v1/reports/cash-flows');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
