<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankAccount;

class BankAccountUpdateTest extends TestCase
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

    public function test_admin_can_update_BankAccount(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'accountName' => 'Updated BankAccount',
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'account_name' => 'Updated BankAccount',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_BankAccount(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create([
            'account_name' => 'Original Name',
            'bank_name' => 'Original Bank'
        ]);

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'accountName' => 'Partially Updated Name'
                // bankName should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'account_name' => 'Partially Updated Name',
            'bank_name' => 'Original Bank'
        ]);
    }

    public function test_admin_can_update_BankAccount_metadata(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertOk();
        
        $bankAccount->refresh();
        $this->assertEquals($metadata, $bankAccount->metadata);
    }

    public function test_customer_user_cannot_update_BankAccount(): void
    {
        $customer = $this->getCustomerUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'accountName' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_BankAccount(): void
    {
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'accountName' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_BankAccount(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-accounts',
            'id' => '999999',
            'attributes' => [
                'accountName' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch('/api/v1/bank-accounts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_BankAccount_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $bankAccount = BankAccount::factory()->create();

        $data = [
            'type' => 'bank-accounts',
            'id' => (string) $bankAccount->id,
            'attributes' => [
                'accountName' => '', // Empty name
                'isActive' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-accounts')
            ->withData($data)
            ->patch("/api/v1/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(422);
    }
}
