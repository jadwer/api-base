<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatement;

class BankStatementShowTest extends TestCase
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

    public function test_admin_can_view_BankStatement(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'bankAccountId',
                        'statementDate',
                        'importSource',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_BankStatement_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $bankStatement = BankStatement::factory()->create(['statement_date' => now(), 'import_source' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'bankAccountId',
                        'statementDate',
                        'importSource',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_BankStatement_with_permission(): void
    {
        $tech = $this->getTechUser();
        $bankStatement = BankStatement::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_BankStatement(): void
    {
        $customer = $this->getCustomerUser();
        $bankStatement = BankStatement::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_BankStatement(): void
    {
        $bankStatement = BankStatement::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_BankStatement(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get('/api/v1/bank-statements/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $bankStatement = BankStatement::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statements')
            ->get("/api/v1/bank-statements/{$bankStatement->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
