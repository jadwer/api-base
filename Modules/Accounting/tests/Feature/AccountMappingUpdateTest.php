<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingUpdateTest extends TestCase
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

    public function test_admin_can_update_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'name' => 'Updated AccountMapping',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('account_mappings', [
            'id' => $accountMapping->id,
            'name' => 'Updated AccountMapping',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('account_mappings', [
            'id' => $accountMapping->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_AccountMapping_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        
        $accountMapping->refresh();
        $this->assertEquals($metadata, $accountMapping->metadata);
    }

    public function test_customer_user_cannot_update_AccountMapping(): void
    {
        $customer = $this->getCustomerUser();
        $accountMapping = AccountMapping::factory()->create();

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_AccountMapping(): void
    {
        $accountMapping = AccountMapping::factory()->create();

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_AccountMapping(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch('/api/v1/account-mappings/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_AccountMapping_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->patch("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(422);
    }
}
