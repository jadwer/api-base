<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\LeaveType;

class LeaveTypeUpdateTest extends TestCase
{
    public function test_admin_can_update_leave_type(): void
    {
        $admin = $this->getAdminUser();
        $leaveType = LeaveType::factory()->create(['name' => 'Old Name', 'days_allowed' => 10]);

        $data = [
            'type' => 'leave-types',
            'id' => (string) $leaveType->id,
            'attributes' => [
                'name' => 'New Name',
                'days_allowed' => 15,
                'paid' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch("/api/v1/leave-types/{$leaveType->id}");

        $response->assertOk();
        $this->assertEquals('New Name', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_update_leave_type(): void
    {
        $tech = $this->getTechUser();
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leave-types',
            'id' => (string) $leaveType->id,
            'attributes' => [
                'description' => 'Updated by tech'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch("/api/v1/leave-types/{$leaveType->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_update_leave_type(): void
    {
        $customer = $this->getCustomerUser();
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leave-types',
            'id' => (string) $leaveType->id,
            'attributes' => [
                'name' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_leave_type(): void
    {
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leave-types',
            'id' => (string) $leaveType->id,
            'attributes' => [
                'name' => 'Should not update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_leave_type(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'id' => '99999',
            'attributes' => [
                'name' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch('/api/v1/leave-types/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rules_apply_on_update(): void
    {
        $admin = $this->getAdminUser();
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leave-types',
            'id' => (string) $leaveType->id,
            'attributes' => [
                'days_allowed' => 'not_a_number'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->patch("/api/v1/leave-types/{$leaveType->id}");

        $response->assertStatus(422);
    }
}
