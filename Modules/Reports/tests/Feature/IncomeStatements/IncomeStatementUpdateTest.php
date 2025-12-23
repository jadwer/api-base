<?php

namespace Modules\Reports\Tests\Feature\IncomeStatements;

use Tests\TestCase;

class IncomeStatementUpdateTest extends TestCase
{

    /** @test */
    public function cannot_update_balance_sheets()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'income-statements',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->withData($data)
            ->patch('/api/v1/reports/income-statements/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'income-statements',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->withData($data)
            ->patch('/api/v1/reports/income-statements/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
