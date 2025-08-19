<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineDestroyTest extends TestCase
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

    public function test_admin_can_delete_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('bank_statement_lines', [
            'id' => $bankStatementLine->id
        ]);
    }

    public function test_admin_can_delete_BankStatementLine_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('bank_statement_lines', [
            'id' => $bankStatementLine->id
        ]);
    }

    public function test_can_delete_inactive_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('bank_statement_lines', [
            'id' => $bankStatementLine->id
        ]);
    }

    public function test_customer_user_cannot_delete_BankStatementLine(): void
    {
        $customer = $this->getCustomerUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $bankStatementLine->id
        ]);
    }

    public function test_guest_cannot_delete_BankStatementLine(): void
    {
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $bankStatementLine->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_BankStatementLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete('/api/v1/bank-statement-lines/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $bankStatementLine = BankStatementLine::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('bank-statement-lines')
            ->delete("/api/v1/bank-statement-lines/{$bankStatementLine->id}");

        $response2->assertStatus(404);
    }
}
