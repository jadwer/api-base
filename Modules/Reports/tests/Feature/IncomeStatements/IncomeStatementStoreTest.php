<?php

namespace Modules\Reports\Tests\Feature\IncomeStatements;

use Tests\TestCase;

class IncomeStatementStoreTest extends TestCase
{

    public function test_cannot_create_balance_sheets_directly()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'income-statements',
            'attributes' => [
                'startDate' => '2025-10-30',
                'currency' => 'MXN',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->withData($data)
            ->post('/api/v1/reports/income-statements');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not created
        $this->assertContains($response->status(), [403, 405]);
    }

    public function test_admin_cannot_create_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'income-statements',
            'attributes' => [
                'startDate' => '2025-10-30',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->withData($data)
            ->post('/api/v1/reports/income-statements');

        // Reports cannot be created directly
        $this->assertContains($response->status(), [403, 405]);
    }
}
