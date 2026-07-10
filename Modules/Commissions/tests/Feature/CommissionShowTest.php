<?php

namespace Modules\Commissions\Tests\Feature;

use Modules\Commissions\Models\Commission;
use Tests\TestCase;

class CommissionShowTest extends TestCase
{
    public function test_admin_can_view_commission(): void
    {
        $admin = $this->getAdminUser();

        $commission = Commission::factory()->earned()->create([
            'base_amount' => 1000.00,
            'commission_pct' => 10.00,
            'commission_amount' => 100.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions/' . $commission->id);

        $response->assertOk();
        $this->assertEquals('earned', $response->json('data.attributes.status'));
        $this->assertEquals(100.00, $response->json('data.attributes.commissionAmount'));
        $this->assertEquals(10.00, $response->json('data.attributes.commissionPct'));
    }

    public function test_customer_cannot_view_commission(): void
    {
        $customer = $this->getCustomerUser();

        $commission = Commission::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions/' . $commission->id);

        $response->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_commission(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions/999999');

        $response->assertStatus(404);
    }
}
