<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountDestroyTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_delete_BankAccount(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_admin_can_delete_BankAccount_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_can_delete_inactive_BankAccount(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }


    public function test_customer_user_cannot_delete_BankAccount(): void
    {
        $customer = $this->getCustomerUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_guest_cannot_delete_BankAccount(): void
    {
        $bankAccount = BankAccount::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_BankAccount(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete('/api/v1/bank-accounts/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->delete("/api/v1/bank-accounts/{$bankAccount->id}");

        $response2->assertStatus(404);
    }
}
