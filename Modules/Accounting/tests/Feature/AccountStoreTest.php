<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\Account;

class AccountStoreTest extends TestCase
{
    public function test_admin_can_create_Account(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'TEST-001',
                'name' => 'Test Account',
                'accountType' => 'asset'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertCreated();

        $this->assertDatabaseHas('accounts', [
            'code' => 'TEST-001',
            'name' => 'Test Account',
            'account_type' => 'asset'
        ]);
    }

    public function test_admin_can_create_Account_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'MIN-001',
                'name' => 'Minimal'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Account(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'FORBIDDEN',
                'name' => 'Unauthorized'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Account(): void
    {
        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'FORBIDDEN',
                'name' => 'Unauthorized'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Account_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(422);
    }

    public function test_cannot_create_Account_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => '',
                'name' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(422);
    }
}
