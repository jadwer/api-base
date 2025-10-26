<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingUpdateTest extends TestCase
{
    public function test_admin_can_update_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'mappingType' => 'updated_type',
                'isActive' => false
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
            'mapping_type' => 'updated_type',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create([
            'mapping_type' => 'original_type',
            'is_active' => true
        ]);

        $data = [
            'type' => 'account-mappings',
            'id' => (string) $accountMapping->id,
            'attributes' => [
                'mappingType' => 'partial_type'
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
            'mapping_type' => 'original_type',
            'is_active' => true
        ]);
    }

    public function test_admin_can_update_AccountMapping_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
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
                'mappingType' => 'forbidden'
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
                'mappingType' => 'forbidden'
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
                'mappingType' => 'forbidden'
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
                'mappingType' => '',
                'isActive' => 'not_boolean'
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
