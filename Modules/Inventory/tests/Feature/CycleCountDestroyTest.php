<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;

class CycleCountDestroyTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_delete_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->scheduled()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->delete('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('cycle_counts', [
            'id' => $cycleCount->id,
        ]);
    }

    public function test_admin_can_delete_cancelled_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->create([
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->delete('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('cycle_counts', [
            'id' => $cycleCount->id,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_cycle_count(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->delete('/api/v1/cycle-counts/99999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_delete_cycle_count(): void
    {
        $cycleCount = CycleCount::factory()->create();

        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->delete('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_delete_cycle_count(): void
    {
        $customer = $this->getCustomerUser();
        $cycleCount = CycleCount::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->delete('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertForbidden();
    }
}
