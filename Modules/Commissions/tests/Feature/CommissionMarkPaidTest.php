<?php

namespace Modules\Commissions\Tests\Feature;

use Modules\Commissions\Models\Commission;
use Tests\TestCase;

class CommissionMarkPaidTest extends TestCase
{
    public function test_admin_can_mark_earned_commission_as_paid(): void
    {
        $admin = $this->getAdminUser();

        $commission = Commission::factory()->earned()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/commissions/{$commission->id}/mark-paid", [
                'payment_reference' => 'TRANSF-2026-07-001',
            ]);

        $response->assertOk();

        $commission->refresh();
        $this->assertEquals('paid', $commission->status);
        $this->assertNotNull($commission->paid_at);
        $this->assertEquals('TRANSF-2026-07-001', $commission->payment_reference);
    }

    public function test_cannot_mark_pending_commission_as_paid(): void
    {
        $admin = $this->getAdminUser();

        $commission = Commission::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/commissions/{$commission->id}/mark-paid", [
                'payment_reference' => 'TRANSF-2026-07-002',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('pending', $commission->fresh()->status);
    }

    public function test_payment_reference_is_required(): void
    {
        $admin = $this->getAdminUser();

        $commission = Commission::factory()->earned()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/commissions/{$commission->id}/mark-paid", []);

        $response->assertStatus(422);
        $this->assertEquals('earned', $commission->fresh()->status);
    }

    public function test_returns_404_for_nonexistent_commission(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/999999/mark-paid', [
                'payment_reference' => 'TRANSF-X',
            ]);

        $response->assertStatus(404);
    }

    public function test_customer_cannot_mark_commission_as_paid(): void
    {
        $customer = $this->getCustomerUser();

        $commission = Commission::factory()->earned()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/commissions/{$commission->id}/mark-paid", [
                'payment_reference' => 'TRANSF-X',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('earned', $commission->fresh()->status);
    }

    public function test_guest_cannot_mark_commission_as_paid(): void
    {
        $commission = Commission::factory()->earned()->create();

        $response = $this->postJson("/api/v1/commissions/{$commission->id}/mark-paid", [
            'payment_reference' => 'TRANSF-X',
        ]);

        $response->assertStatus(401);
    }
}
