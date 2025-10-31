<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\LeaveType;

class LeaveTypeDestroyTest extends TestCase
{
    public function test_admin_can_delete_leave_type(): void
    {
        $admin = $this->getAdminUser();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->delete("/api/v1/leave-types/{$leaveType->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leave_types', ['id' => $leaveType->id]);
    }

    public function test_tech_user_can_delete_leave_type(): void
    {
        $tech = $this->getTechUser();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->delete("/api/v1/leave-types/{$leaveType->id}");

        $response->assertNoContent();
    }

    public function test_customer_user_cannot_delete_leave_type(): void
    {
        $customer = $this->getCustomerUser();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->delete("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    public function test_guest_cannot_delete_leave_type(): void
    {
        $leaveType = LeaveType::factory()->create();

        $response = $this->jsonApi()
            ->expects('leave-types')
            ->delete("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    public function test_returns_404_for_nonexistent_leave_type(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->delete('/api/v1/leave-types/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_leave_type_does_not_affect_other_leave_types(): void
    {
        $admin = $this->getAdminUser();

        $leaveType1 = LeaveType::factory()->create();
        $leaveType2 = LeaveType::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->delete("/api/v1/leave-types/{$leaveType1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leave_types', ['id' => $leaveType1->id]);
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType2->id]);
    }
}
