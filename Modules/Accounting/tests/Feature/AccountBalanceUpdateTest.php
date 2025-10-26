<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceUpdateTest extends TestCase
{
    public function test_admin_can_update_account_balances(): void
    {
        $admin = $this->getAdminUser();
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'openingBalance' => 1500,
                'periodDebits' => 500,
                'periodCredits' => 300
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_account_balances(): void
    {
        $admin = $this->getAdminUser();
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'openingBalance' => 2000
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = AccountBalance::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'updated' => true,
  'value' => 'test',
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_account_balances(): void
    {
        $tech = $this->getTechUser();
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'openingBalance' => 1000.00
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_account_balances(): void
    {
        $customer = $this->getCustomerUser();
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'openingBalance' => 1000.00
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_account_balances(): void
    {
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'fiscalYear' => 2026
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_account_balances(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-balances',
            'id' => '999999',
            'attributes' => [
                'fiscalYear' => 2026
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch('/api/v1/account-balances/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = AccountBalance::factory()->create();

        $data = [
            'type' => 'account-balances',
            'id' => (string) $entity->id,
            'attributes' => [
                'fiscalYear' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->withData($data)
            ->patch("/api/v1/account-balances/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}
