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
                'nature' => 'test string',
                'level' => 100,
                'currency' => 'test string',
                'isPostable' => true,
                'isCashFlow' => true,
                'status' => 'active',
                'metadata' => 'test value'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('accounts', ['code' => 'TEST123', 'name' => 'Test Name', 'account_type' => 'test string', 'nature' => 'test string', 'level' => 100, 'currency' => 'test string', 'is_postable' => true, 'is_cash_flow' => true, 'status' => 'active', 'metadata' => 'test value']);
    }

    public function test_admin_can_create_Account_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'isPostable' => true,
                'isCashFlow' => true
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
                'name' => 'Unauthorized Account',
                'is_active' => true
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
                'name' => 'Guest Account',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->post('/api/v1/accounts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_Account_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
