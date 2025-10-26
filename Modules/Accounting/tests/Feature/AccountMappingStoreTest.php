<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingStoreTest extends TestCase
{
    public function test_admin_can_create_AccountMapping(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'source_system',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertCreated();

        $this->assertDatabaseHas('account_mappings', [
            'mapping_type' => 'source_system',
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_AccountMapping_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'minimal_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_AccountMapping(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'forbidden',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_AccountMapping(): void
    {
        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'forbidden',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(401);
    }

    public function test_cannot_create_AccountMapping_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(422);
    }

    public function test_cannot_create_AccountMapping_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => '',
                'isActive' => 'not_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(422);
    }
}
