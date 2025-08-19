<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineShowTest extends TestCase
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

    public function test_admin_can_view_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'bankStatementId',
                        'txnDate',
                        'amount',
                        'counterparty',
                        'reference',
                        'fitid',
                        'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_BankStatementLine_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $bankStatementLine = BankStatementLine::factory()->create(['txn_date' => now(), 'amount' => 99.99, 'counterparty' => 'test string', 'reference' => 'test string', 'fitid' => 'test string', 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'bankStatementId',
                        'txnDate',
                        'amount',
                        'counterparty',
                        'reference',
                        'fitid',
                        'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_BankStatementLine_with_permission(): void
    {
        $tech = $this->getTechUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_BankStatementLine(): void
    {
        $customer = $this->getCustomerUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_BankStatementLine(): void
    {
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get('/api/v1/bank-statement-lines/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->get("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
