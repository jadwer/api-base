<?php

namespace Modules\Reports\Tests\Feature\IncomeStatements;

use Tests\TestCase;

class IncomeStatementDestroyTest extends TestCase
{

    /** @test */
    public function cannot_delete_balance_sheets()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->delete('/api/v1/reports/income-statements/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_delete_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->delete('/api/v1/reports/income-statements/1');

        // Reports cannot be deleted
        $this->assertContains($response->status(), [403, 405]);
    }
}
