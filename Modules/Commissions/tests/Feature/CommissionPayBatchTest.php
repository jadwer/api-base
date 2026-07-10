<?php

namespace Modules\Commissions\Tests\Feature;

use Modules\Commissions\Models\Commission;
use Tests\TestCase;

class CommissionPayBatchTest extends TestCase
{
    public function test_admin_can_pay_batch_of_earned_commissions(): void
    {
        $admin = $this->getAdminUser();

        $commissions = Commission::factory()->count(3)->earned()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', [
                'ids' => $commissions->pluck('id')->all(),
                'payment_reference' => 'CORTE-2026-07',
            ]);

        $response->assertOk();

        foreach ($commissions as $commission) {
            $commission->refresh();
            $this->assertEquals('paid', $commission->status);
            $this->assertEquals('CORTE-2026-07', $commission->payment_reference);
            $this->assertNotNull($commission->paid_at);
        }
    }

    /**
     * Edge case 5: a batch containing an already paid commission returns 422
     * and produces NO partial effects (transactional).
     */
    public function test_pay_batch_with_already_paid_commission_returns_422_without_partial_effects(): void
    {
        $admin = $this->getAdminUser();

        $earned = Commission::factory()->count(2)->earned()->create();
        $alreadyPaid = Commission::factory()->paid()->create([
            'payment_reference' => 'OLD-REF',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', [
                'ids' => $earned->pluck('id')->push($alreadyPaid->id)->all(),
                'payment_reference' => 'CORTE-2026-07',
            ]);

        $response->assertStatus(422);

        // No partial effects: earned rows untouched
        foreach ($earned as $commission) {
            $commission->refresh();
            $this->assertEquals('earned', $commission->status);
            $this->assertNull($commission->payment_reference);
        }

        // Already paid row keeps its original reference
        $this->assertEquals('OLD-REF', $alreadyPaid->fresh()->payment_reference);
    }

    public function test_pay_batch_with_pending_commission_returns_422_without_partial_effects(): void
    {
        $admin = $this->getAdminUser();

        $earned = Commission::factory()->earned()->create();
        $pending = Commission::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', [
                'ids' => [$earned->id, $pending->id],
                'payment_reference' => 'CORTE-2026-07',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('earned', $earned->fresh()->status);
        $this->assertEquals('pending', $pending->fresh()->status);
    }

    public function test_pay_batch_with_nonexistent_id_returns_422(): void
    {
        $admin = $this->getAdminUser();

        $earned = Commission::factory()->earned()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', [
                'ids' => [$earned->id, 999999],
                'payment_reference' => 'CORTE-2026-07',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('earned', $earned->fresh()->status);
    }

    public function test_pay_batch_requires_ids_and_payment_reference(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', []);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_pay_batch(): void
    {
        $customer = $this->getCustomerUser();

        $earned = Commission::factory()->earned()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/commissions/pay-batch', [
                'ids' => [$earned->id],
                'payment_reference' => 'CORTE-2026-07',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('earned', $earned->fresh()->status);
    }
}
