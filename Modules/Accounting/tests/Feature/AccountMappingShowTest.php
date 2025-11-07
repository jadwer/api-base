<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingShowTest extends TestCase
{



    public function test_admin_can_view_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'mappingType',
                        'account_id',
                        'version',
                        'effectiveFrom',
                        'effectiveTo',
                        'is_active',
                        'createdById',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_AccountMapping_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $accountMapping = AccountMapping::factory()->create(['mapping_type' => 'test string', 'version' => 100, 'effective_from' => now(), 'effective_to' => now(), 'is_active' => true, 'notes' => 'test description']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'mappingType',
                        'account_id',
                        'version',
                        'effectiveFrom',
                        'effectiveTo',
                        'is_active',
                        'createdById',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_AccountMapping_with_permission(): void
    {
        $tech = $this->getTechUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_AccountMapping(): void
    {
        $customer = $this->getCustomerUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_AccountMapping(): void
    {
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_AccountMapping(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get('/api/v1/account-mappings/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->get("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
