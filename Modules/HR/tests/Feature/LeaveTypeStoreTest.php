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
                'days_allowed' => 10,
                'requires_approval' => true,
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
                'days_allowed' => 15,
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
                'days_allowed' => 5,
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
                'days_allowed' => 5,
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
                'days_allowed' => 10,
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

    public function test_name_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->create(['name' => 'Existing Leave']);

        $data = [
            'type' => 'leave-types',
            'attributes' => [
                'name' => 'Existing Leave',
                'days_allowed' => 10,
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
                'days_allowed' => 'not_a_number',
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
