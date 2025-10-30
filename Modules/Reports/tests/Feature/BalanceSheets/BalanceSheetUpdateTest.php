<?php

namespace Modules\Reports\Tests\Feature\BalanceSheets;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BalanceSheetUpdateTest extends TestCase
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

    /** @test */
    public function cannot_update_balance_sheets()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'balance-sheets',
            'id' => '1',
            'attributes' => [
                'currency' => 'USD',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->withData($data)
            ->patch('/api/v1/reports/balance-sheets/1');

        // Should return 405 Method Not Allowed or 403 Forbidden
        // Balance sheets are generated, not edited
        $this->assertContains($response->status(), [403, 405]);
    }

    /** @test */
    public function admin_cannot_update_balance_sheet()
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'balance-sheets',
            'id' => '1',
            'attributes' => [
                'currency' => 'EUR',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('balance-sheets')
            ->withData($data)
            ->patch('/api/v1/reports/balance-sheets/1');

        // Reports cannot be updated
        $this->assertContains($response->status(), [403, 405]);
    }
}
