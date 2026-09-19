<?php

namespace Modules\Branch\Tests\Feature;

use Tests\TestCase;
use Modules\Branch\Models\Branch;

class BranchIndexTest extends TestCase
{
    public function test_admin_can_list_Branchs(): void
    {
        $admin = $this->getAdminUser();

        Branch::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches');

        $response->assertOk();
    }


    public function test_admin_can_sort_Branchs_by_name(): void
    {
        $admin = $this->getAdminUser();

        Branch::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches?sort=name');

        $response->assertOk();
    }


    public function test_admin_can_filter_Branchs_by_name(): void
    {
        $admin = $this->getAdminUser();

        Branch::factory()->create(['name' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches?filter[name]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Branchs_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches');

        $response->assertOk();
    }

    public function test_customer_user_can_list_Branchs_read_only(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches');

        $response->assertOk();
    }

    public function test_guest_cannot_list_Branchs(): void
    {
        $response = $this->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Branchs(): void
    {
        $admin = $this->getAdminUser();

        Branch::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
