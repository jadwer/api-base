<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;

class CycleCountShowTest extends TestCase
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

    public function test_admin_can_show_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => (string) $cycleCount->id,
                'type' => 'cycle-counts',
            ]
        ]);
    }

    public function test_admin_can_include_warehouse_relationship(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->includePaths('warehouse')
            ->get('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'relationships' => ['warehouse']
            ],
            'included'
        ]);
    }

    public function test_show_returns_404_for_non_existent_cycle_count(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts/99999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_show_cycle_count(): void
    {
        $cycleCount = CycleCount::factory()->create();

        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_show_cycle_count(): void
    {
        $customer = $this->getCustomerUser();
        $cycleCount = CycleCount::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertForbidden();
    }
}
