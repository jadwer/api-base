<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineDestroyTest extends TestCase
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

    public function test_admin_can_delete_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id
        ]);
    }

    public function test_admin_can_delete_APInvoiceLine_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id
        ]);
    }

    public function test_can_delete_inactive_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id
        ]);
    }

    public function test_customer_user_cannot_delete_APInvoiceLine(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id
        ]);
    }

    public function test_guest_cannot_delete_APInvoiceLine(): void
    {
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete('/api/v1/a-p-invoice-lines/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->delete("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response2->assertStatus(404);
    }
}
