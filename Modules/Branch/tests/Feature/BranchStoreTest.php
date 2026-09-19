<?php

namespace Modules\Branch\Tests\Feature;

use Tests\TestCase;
use Modules\Branch\Models\Branch;


class BranchStoreTest extends TestCase
{
    public function test_admin_can_create_Branch(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'branches',
            'attributes' => [
                'name' => 'Test Name',
                'code' => 'Test Code',
                'address' => 'Test description for address',
                'city' => 'Test City',
                'state' => 'Test State',
                'postalCode' => '52978',
                'phone' => 'Test Phone',
                'email' => 'sucursal@example.com',
                'isActive' => true,
                'isMain' => true,
            ],

        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->post('/api/v1/branches');

        $response->assertCreated();

        $this->assertDatabaseHas('branches', ['name' => 'Test Name', 'code' => 'Test Code']);
    }

    public function test_customer_user_cannot_create_Branch(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'branches',
            'attributes' => [
                'name' => 'Test Name',
                'code' => 'Test Code',
                'isActive' => true,
                'isMain' => true,
            ],

        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->post('/api/v1/branches');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Branch(): void
    {
        $data = [
            'type' => 'branches',
            'attributes' => [
                'name' => 'Test Name',
                'code' => 'Test Code',
                'isActive' => true,
                'isMain' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->post('/api/v1/branches');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Branch_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'branches',
            'attributes' => (object) [],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->post('/api/v1/branches');

        $response->assertStatus(422);
    }
}
