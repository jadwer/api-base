<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Account;

class AccountStoreTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_create_Account(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'accountType' => 'test string',
                'level' => 100,
                'currency' => 'test string',
                'isPostable' => true,
                'status' => 'active',
                'metadata' => ['test_key' => 'test_value']
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('accounts', ['code' => 'TEST123', 'name' => 'Test Name', 'account_type' => 'test string', 'level' => 100, 'currency' => 'test string', 'is_postable' => true, 'status' => 'active']);
    }

    public function test_admin_can_create_Account_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'accountType' => 'asset',
                'level' => 1,
                'status' => 'active',
                'isPostable' => true
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
                'code' => 'UNAUTH123',
                'name' => 'Unauthorized Account',
                'accountType' => 'asset',
                'level' => 1,
                'status' => 'active',
                'isPostable' => true
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
                'code' => 'GUEST123',
                'name' => 'Guest Account',
                'accountType' => 'asset',
                'level' => 1,
                'status' => 'active',
                'isPostable' => true
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
                'currency' => 'USD' // Only optional field
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/code', '/data/attributes/name', '/data/attributes/accountType', '/data/attributes/level', '/data/attributes/isPostable', '/data/attributes/status'], $response);
    }

    public function test_cannot_create_Account_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => '',
                'name' => '', // Empty name
                'accountType' => '',
                'level' => 'not_number',
                'isPostable' => 'not_boolean' // Invalid boolean
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
