<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\LeaveType;

class LeaveTypeStoreTest extends TestCase
{
    public function test_admin_can_create_leave_type(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'Medical leave',
                'daysAllowed' => 10,
                'requiresApproval' => true,
                'paid' => true,
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertCreated();
        $this->assertEquals('Sick Leave', $response->json('data.attributes.name'));

        $this->assertDatabaseHas('leave_types', [
            'name' => 'Sick Leave',
            'code' => 'SICK',
            'paid' => true
        ]);
    }

    public function test_tech_user_can_create_leave_type(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Vacation',
                'code' => 'VAC',
                'daysAllowed' => 15,
                'paid' => true,
                'active' => true
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_leave_type(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Should Not Create',
                'code' => 'SNC',
                'daysAllowed' => 5,
                'active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_leave_type(): void
    {
        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Should Not Create',
                'code' => 'SNC',
                'daysAllowed' => 5,
                'active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(401);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'code' => 'TST',
                'daysAllowed' => 10,
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(422);
    }

    public function test_code_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Test Leave',
                'daysAllowed' => 10,
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(422);
    }

    public function test_code_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->create(['code' => 'EXIST']);

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'New Leave',
                'code' => 'EXIST',
                'daysAllowed' => 10,
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(422);
    }

    public function test_days_allowed_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Test Leave',
                'code' => 'TST',
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(422);
    }

    public function test_days_allowed_must_be_integer(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Test Leave',
                'code' => 'TST',
                'daysAllowed' => 'not_a_number',
                'active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->withData($data)
            ->post('/api/v1/leave-types');

        $response->assertStatus(422);
    }
}
