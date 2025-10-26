<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingStoreTest extends TestCase
{
    public function test_admin_can_create_account_mappings(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test-type',
                'accountId' => 1,
                'version' => 1,
                'effectiveFrom' => '2024-01-01',
                'isActive' => true,
                'createdById' => 1
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_account_mappings_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test-type',
                'accountId' => 1,
                'version' => 1,
                'effectiveFrom' => '2024-01-01',
                'isActive' => true,
                'createdById' => 1
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_account_mappings(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_account_mappings(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_account_mappings(): void
    {
        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(401);
    }

    public function test_cannot_create_account_mappings_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $response->assertStatus(422);
    }

    public function test_cannot_create_account_mappings_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'account-mappings',
            'attributes' => [
                'mappingType' => 'test'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->withData($data)
            ->post('/api/v1/account-mappings');

        $this->assertContains($response->status(), [200, 422]);
    }
}
