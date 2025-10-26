<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\Account;

class AccountUpdateTest extends TestCase
{
    public function test_admin_can_update_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'code' => 'UPD-001',
                'name' => 'Updated Account',
                'accountType' => 'liability'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertOk();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => 'UPD-001',
            'name' => 'Updated Account',
            'account_type' => 'liability'
        ]);
    }

    public function test_admin_can_partially_update_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create([
            'code' => 'OLD-001',
            'name' => 'Original Name'
        ]);

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'name' => 'Partially Updated'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertOk();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => 'OLD-001',
            'name' => 'Original Name'
        ]);
    }

    public function test_admin_can_update_Account_metadata(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertOk();

        $account->refresh();
        $this->assertEquals($metadata, $account->metadata);
    }

    public function test_customer_user_cannot_update_Account(): void
    {
        $customer = $this->getCustomerUser();
        $account = Account::factory()->create();

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'code' => 'FORBIDDEN'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_Account(): void
    {
        $account = Account::factory()->create();

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'code' => 'FORBIDDEN'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Account(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'accounts',
            'id' => '999999',
            'attributes' => [
                'code' => 'FORBIDDEN'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch('/api/v1/accounts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_Account_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'code' => '',
                'name' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->withData($data)
            ->patch("/api/v1/accounts/{$account->id}");

        $response->assertStatus(422);
    }
}
