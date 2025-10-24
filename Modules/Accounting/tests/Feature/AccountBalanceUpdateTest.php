<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceUpdateTest extends TestCase
{



    public function test_admin_can_update_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'name' => 'Updated AccountBalance',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id,
            'name' => 'Updated AccountBalance',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_AccountBalance_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertOk();
        
        $accountBalance->refresh();
        $this->assertEquals($metadata, $accountBalance->metadata);
    }

    public function test_customer_user_cannot_update_AccountBalance(): void
    {
        $customer = $this->getCustomerUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_AccountBalance(): void
    {
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_AccountBalance(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch('/api/v1/account-balances/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_AccountBalance_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $accountBalance->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(422);
    }
}
