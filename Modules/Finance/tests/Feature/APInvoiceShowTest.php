<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceShowTest extends TestCase
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

    public function test_admin_can_view_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'invoiceNumber',
                        'invoiceDate',
                        'dueDate',
                        'currency',
                        'exchangeRate',
                        'subtotal',
                        'taxTotal',
                        'total',
                        'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_APInvoice_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aPInvoice = APInvoice::factory()->create(['invoice_number' => 'test string', 'invoice_date' => now(), 'due_date' => now(), 'currency' => 'test string', 'exchange_rate' => 99.99, 'subtotal' => 99.99, 'tax_total' => 99.99, 'total' => 99.99, 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'invoiceNumber',
                        'invoiceDate',
                        'dueDate',
                        'currency',
                        'exchangeRate',
                        'subtotal',
                        'taxTotal',
                        'total',
                        'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_APInvoice_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_APInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_APInvoice(): void
    {
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
