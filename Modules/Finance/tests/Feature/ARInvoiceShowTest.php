<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceShowTest extends TestCase
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

    public function test_admin_can_view_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

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

    public function test_admin_can_view_ARInvoice_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aRInvoice = ARInvoice::factory()->create(['invoice_number' => 'test string', 'invoice_date' => now(), 'due_date' => now(), 'currency' => 'test string', 'exchange_rate' => 99.99, 'subtotal' => 99.99, 'tax_total' => 99.99, 'total' => 99.99, 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

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

    public function test_tech_user_can_view_ARInvoice_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ARInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ARInvoice(): void
    {
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ARInvoice(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
