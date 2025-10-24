<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Account;

class AccountShowTest extends TestCase
{



    public function test_admin_can_view_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'code',
                        'name',
                        'accountType',
                        'nature',
                        'level',
                        'parentId',
                        'currency',
                        'isPostable',
                        'isCashFlow',
                        'status',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_Account_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $account = Account::factory()->create(['code' => 'TEST123', 'name' => 'Test Name', 'account_type' => 'test string', 'nature' => 'test string', 'level' => 100, 'currency' => 'test string', 'is_postable' => true, 'is_cash_flow' => true, 'status' => 'active', 'metadata' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'code',
                        'name',
                        'accountType',
                        'nature',
                        'level',
                        'parentId',
                        'currency',
                        'isPostable',
                        'isCashFlow',
                        'status',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_Account_with_permission(): void
    {
        $tech = $this->getTechUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_Account(): void
    {
        $customer = $this->getCustomerUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_Account(): void
    {
        $account = Account::factory()->create();

        $response = $this->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Account(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get("/api/v1/accounts/{$account->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
