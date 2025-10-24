<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceDestroyTest extends TestCase
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

    public function test_admin_can_delete_AccountBalance(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('account_balances', [
            'id' => $accountBalance->id
        ]);
    }

    public function test_admin_can_delete_AccountBalance_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('account_balances', [
            'id' => $accountBalance->id
        ]);
    }

    public function test_can_delete_AccountBalance_with_custom_fiscal_period(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create([
            'fiscal_year' => 2024,
            'fiscal_month' => 12
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('account_balances', [
            'id' => $accountBalance->id
        ]);
    }

    public function test_customer_user_cannot_delete_AccountBalance(): void
    {
        $customer = $this->getCustomerUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id
        ]);
    }

    public function test_guest_cannot_delete_AccountBalance(): void
    {
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_AccountBalance(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete('/api/v1/account-balances/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $accountBalance = AccountBalance::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('account-balances')
            ->delete("/api/v1/account-balances/{$accountBalance->id}");

        $response2->assertStatus(404);
    }
}
