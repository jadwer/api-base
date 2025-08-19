<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineStoreTest extends TestCase
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

    public function test_admin_can_create_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [
                'txnDate' => '2024-01-01',
                'amount' => 99.99,
                'counterparty' => 'test string',
                'reference' => 'test string',
                'fitid' => 'test string',
                'status' => 'active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertCreated();
        
        $this->assertDatabaseHas('bank_statement_lines', ['txn_date' => 'test value', 'amount' => 99.99, 'counterparty' => 'test string', 'reference' => 'test string', 'fitid' => 'test string', 'status' => 'active']);
    }

    public function test_admin_can_create_BankStatementLine_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_BankStatementLine(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [
                'name' => 'Unauthorized BankStatementLine',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_BankStatementLine(): void
    {
        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [
                'name' => 'Guest BankStatementLine',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertStatus(401);
    }

    public function test_cannot_create_BankStatementLine_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_BankStatementLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'bank-statement-lines',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->withData($data)
            ->post('/api/v1/bank-statement-lines');

        $response->assertStatus(422);
    }
}
