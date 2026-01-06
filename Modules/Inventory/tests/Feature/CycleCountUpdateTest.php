<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;

class CycleCountUpdateTest extends TestCase
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

    public function test_admin_can_update_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->scheduled()->create([
            'notes' => 'Original notes',
        ]);

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $this->assertDatabaseHas('cycle_counts', [
            'id' => $cycleCount->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_admin_can_update_cycle_count_status(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->scheduled()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'status' => 'in_progress',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $this->assertDatabaseHas('cycle_counts', [
            'id' => $cycleCount->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_admin_can_record_counted_quantity(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->inProgress()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'countedQuantity' => 150.5,
                'status' => 'completed',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $this->assertDatabaseHas('cycle_counts', [
            'id' => $cycleCount->id,
            'counted_quantity' => 150.5,
            'status' => 'completed',
        ]);
    }

    public function test_update_validates_status(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'status' => 'invalid_status',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_update_cycle_count(): void
    {
        $cycleCount = CycleCount::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_update_cycle_count(): void
    {
        $customer = $this->getCustomerUser();
        $cycleCount = CycleCount::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertForbidden();
    }
}
