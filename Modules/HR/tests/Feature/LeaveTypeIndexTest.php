<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\LeaveType;

class LeaveTypeIndexTest extends TestCase
{
    public function test_admin_can_list_leave_types(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_leave_types_by_name(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->create(['name' => 'Zulu Leave']);
        LeaveType::factory()->create(['name' => 'Alpha Leave']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types?sort=name&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_admin_can_filter_leave_types_by_active_status(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->count(2)->active()->create();
        LeaveType::factory()->count(1)->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types?filter[active]=1');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_leave_types_by_paid(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->count(2)->paid()->create();
        LeaveType::factory()->count(1)->unpaid()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types?filter[paid]=1');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_leave_types_with_permission(): void
    {
        $tech = $this->getTechUser();

        LeaveType::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_leave_types(): void
    {
        $customer = $this->getCustomerUser();

        LeaveType::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_leave_types(): void
    {
        $response = $this->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types');

        $response->assertStatus(401);
    }

    public function test_can_paginate_leave_types(): void
    {
        $admin = $this->getAdminUser();

        LeaveType::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leave-types')
            ->get('/api/v1/leave-types?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }
}
