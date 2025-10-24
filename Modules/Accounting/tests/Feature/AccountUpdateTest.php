<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
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
                'name' => 'Updated Account',
                'description' => 'Updated description',
                'is_active' => false
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
            'name' => 'Updated Account',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'accounts',
            'id' => (string) $account->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
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
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_Account_metadata(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
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
                'name' => 'Unauthorized Update'
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
                'name' => 'Guest Update'
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
                'name' => 'Nonexistent Update'
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
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
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
