<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Account;

class AccountDestroyTest extends TestCase
{



    public function test_admin_can_delete_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id
        ]);
    }

    public function test_admin_can_delete_Account_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id
        ]);
    }

    public function test_can_delete_inactive_Account(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id
        ]);
    }

    public function test_customer_user_cannot_delete_Account(): void
    {
        $customer = $this->getCustomerUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id
        ]);
    }

    public function test_guest_cannot_delete_Account(): void
    {
        $account = Account::factory()->create();

        $response = $this->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_Account(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete('/api/v1/accounts/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $account = Account::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->delete("/api/v1/accounts/{$account->id}");

        $response2->assertStatus(404);
    }
}
