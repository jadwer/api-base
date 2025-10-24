<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceDestroyTest extends TestCase
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

    public function test_admin_can_delete_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_invoices', [
            'id' => $aPInvoice->id
        ]);
    }

    public function test_admin_can_delete_APInvoice_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_invoices', [
            'id' => $aPInvoice->id
        ]);
    }

    public function test_can_delete_inactive_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('ap_invoices', [
            'id' => $aPInvoice->id
        ]);
    }


    public function test_customer_user_cannot_delete_APInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('ap_invoices', [
            'id' => $aPInvoice->id
        ]);
    }

    public function test_guest_cannot_delete_APInvoice(): void
    {
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('ap_invoices', [
            'id' => $aPInvoice->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete('/api/v1/a-p-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->delete("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response2->assertStatus(404);
    }
}
