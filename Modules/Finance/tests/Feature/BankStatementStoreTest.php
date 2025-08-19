<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatement;

class BankStatementStoreTest extends TestCase
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

    public function test_admin_can_create_BankStatement(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statements',
            'attributes' => [
                'statementDate' => '2024-01-01',
                'importSource' => 'test string'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertCreated();
        
        $this->assertDatabaseHas('bank_statements', ['statement_date' => 'test value', 'import_source' => 'test string']);
    }

    public function test_admin_can_create_BankStatement_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statements',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_BankStatement(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'bank-statements',
            'attributes' => [
                'name' => 'Unauthorized BankStatement',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_BankStatement(): void
    {
        $data = [
            'type' => 'bank-statements',
            'attributes' => [
                'name' => 'Guest BankStatement',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertStatus(401);
    }

    public function test_cannot_create_BankStatement_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statements',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_BankStatement_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statements',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->withData($data)
            ->post('/api/v1/bank-statements');

        $response->assertStatus(422);
    }
}
