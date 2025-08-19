<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatement;

class BankStatementUpdateTest extends TestCase
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

    public function test_admin_can_update_BankStatement(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create();

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'name' => 'Updated BankStatement',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('bank_statements', [
            'id' => $bankStatement->id,
            'name' => 'Updated BankStatement',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_BankStatement(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('bank_statements', [
            'id' => $bankStatement->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_BankStatement_metadata(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        
        $bankStatement->refresh();
        $this->assertEquals($metadata, $bankStatement->metadata);
    }

    public function test_customer_user_cannot_update_BankStatement(): void
    {
        $customer = $this->getCustomerUser();
        $bankStatement = BankStatement::factory()->create();

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_BankStatement(): void
    {
        $bankStatement = BankStatement::factory()->create();

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_BankStatement(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statements',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch('/api/v1/bank-statements/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_BankStatement_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create();

        $data = [
            'type' => 'bank-statements',
            'id' => (string) $bankStatement->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->patch("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertStatus(422);
    }
}
