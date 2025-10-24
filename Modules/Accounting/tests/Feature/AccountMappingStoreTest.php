<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingStoreTest extends TestCase
{



    public function test_admin_can_create_AccountMapping(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test string',
                'version' => 100,
                'effectiveFrom' => '2024-01-01',
                'effectiveTo' => '2024-01-01',
                'isActive' => true,
                'notes' => 'test description'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertCreated();
        
        $this->assertDatabaseHas('account_mappings', ['mapping_type' => 'test string', 'version' => 100, 'effective_from' => 'test value', 'effective_to' => 'test value', 'is_active' => true, 'notes' => 'test description']);
    }

    public function test_admin_can_create_AccountMapping_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'isActive' => true
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
                'name' => 'Unauthorized AccountMapping',
                'is_active' => true
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
                'name' => 'Guest AccountMapping',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_AccountMapping_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
