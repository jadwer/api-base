<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingDestroyTest extends TestCase
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

    public function test_admin_can_delete_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('account_mappings', [
            'id' => $accountMapping->id
        ]);
    }

    public function test_admin_can_delete_AccountMapping_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('account_mappings', [
            'id' => $accountMapping->id
        ]);
    }

    public function test_can_delete_inactive_AccountMapping(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('account_mappings', [
            'id' => $accountMapping->id
        ]);
    }

    public function test_customer_user_cannot_delete_AccountMapping(): void
    {
        $customer = $this->getCustomerUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('account_mappings', [
            'id' => $accountMapping->id
        ]);
    }

    public function test_guest_cannot_delete_AccountMapping(): void
    {
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('account_mappings', [
            'id' => $accountMapping->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_AccountMapping(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete('/api/v1/account-mappings/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $accountMapping = AccountMapping::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-mappings')
            ->delete("/api/v1/account-mappings/{$accountMapping->id}");

        $response2->assertStatus(404);
    }
}
