<?php

namespace Modules\Reports\Tests\Feature\BalanceSheets;

use Tests\TestCase;

class BalanceSheetStoreTest extends TestCase
{
    public function test_cannot_create_balance_sheets_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'balance-sheets',
            'attributes' => [
                'asOfDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->withData($data)
            ->post('/api/v1/reports/balance-sheets');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    public function test_admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'balance-sheets',
            'attributes' => [
                'asOfDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->withData($data)
            ->post('/api/v1/reports/balance-sheets');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
