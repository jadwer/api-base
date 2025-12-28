<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\LeaveType;

class LeaveTypeShowTest extends TestCase
{
    public function test_admin_can_view_leave_type(): void
    {
        $admin = $this->getAdminUser();

        $leaveType = LeaveType::factory()->create([
            'name' => 'Annual Leave',
            'days_allowed' => 20,
            'paid' => true,
            'active' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get("/api/v1/leave-types/{$leaveType->id}");

        $response->assertOk();
        $this->assertEquals($leaveType->id, $response->json('data.id'));
        $this->assertEquals('Annual Leave', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_view_leave_type(): void
    {
        $tech = $this->getTechUser();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get("/api/v1/leave-types/{$leaveType->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_leave_type(): void
    {
        $customer = $this->getCustomerUser();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_leave_type(): void
    {
        $leaveType = LeaveType::factory()->create();

        $response = $this->jsonApi()
            ->expects('leave-types')
            ->get("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_leave_type(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types/99999');

        $response->assertStatus(404);
    }

    public function test_inactive_leave_type_shows_correct_status(): void
    {
        $admin = $this->getAdminUser();
        $leaveType = LeaveType::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get("/api/v1/leave-types/{$leaveType->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.active'));
    }
}
