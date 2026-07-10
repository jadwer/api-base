<?php

namespace Modules\Commissions\Tests\Feature;

use Modules\Commissions\Models\Commission;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Custom report endpoints: by-period and by-employee.
 */
class CommissionReportsTest extends TestCase
{
    public function test_admin_can_get_commissions_by_period(): void
    {
        $admin = $this->getAdminUser();

        Commission::factory()->earned()->create([
            'earned_at' => '2026-07-05 12:00:00',
            'commission_amount' => 100.00,
        ]);
        Commission::factory()->earned()->create([
            'earned_at' => '2026-07-20 12:00:00',
            'commission_amount' => 50.00,
        ]);
        // Outside the period
        Commission::factory()->earned()->create([
            'earned_at' => '2026-06-15 12:00:00',
            'commission_amount' => 999.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/commissions/by-period?start=2026-07-01&end=2026-07-31');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.count'));
        $this->assertEquals(150.00, $response->json('meta.total_commission_amount'));
    }

    public function test_by_period_can_filter_by_user_and_status(): void
    {
        $admin = $this->getAdminUser();

        $salesperson = User::factory()->create();

        Commission::factory()->earned()->create([
            'user_id' => $salesperson->id,
            'earned_at' => '2026-07-05 12:00:00',
        ]);
        Commission::factory()->earned()->create([
            'earned_at' => '2026-07-06 12:00:00',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/commissions/by-period?start=2026-07-01&end=2026-07-31&user_id={$salesperson->id}&status=earned");

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.count'));
        $this->assertEquals($salesperson->id, $response->json('data.0.user_id'));
    }

    public function test_admin_can_get_commissions_by_employee(): void
    {
        $admin = $this->getAdminUser();

        $salesperson = User::factory()->create();

        Commission::factory()->earned()->create([
            'user_id' => $salesperson->id,
            'earned_at' => '2026-07-05 12:00:00',
            'commission_amount' => 100.00,
        ]);
        Commission::factory()->earned()->create([
            'user_id' => $salesperson->id,
            'earned_at' => '2026-07-10 12:00:00',
            'commission_amount' => 60.00,
        ]);
        // Pending rows are excluded from the payout aggregate
        Commission::factory()->pending()->create([
            'user_id' => $salesperson->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/commissions/by-employee?start=2026-07-01&end=2026-07-31');

        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('user_id', $salesperson->id);
        $this->assertNotNull($row);
        $this->assertEquals(2, $row['commissions_count']);
        $this->assertEquals(160.00, $row['total_commission_amount']);
        $this->assertEquals(160.00, $row['earned_amount']);
        $this->assertEquals(0.00, $row['paid_amount']);
    }

    public function test_customer_cannot_access_reports(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/commissions/by-period')
            ->assertStatus(403);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/commissions/by-employee')
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_reports(): void
    {
        $this->getJson('/api/v1/commissions/by-period')->assertStatus(401);
        $this->getJson('/api/v1/commissions/by-employee')->assertStatus(401);
    }
}
