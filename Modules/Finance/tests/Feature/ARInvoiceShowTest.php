<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceShowTest extends TestCase
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

    public function test_admin_can_view_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'invoiceNumber',
                        'invoiceDate',
                        'dueDate',
                        'contactId',
                        'currency',
                        'subtotal',
                        'taxAmount',
                        'totalAmount',
                        'paidAmount',
                        'status',
                        'journalEntryId',
                        'notes',
                        'metadata',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ARInvoice_with_specific_data(): void
    {
        $admin = $this->getAdminUser();

        $aRInvoice = ARInvoice::factory()->create([
            'invoice_number' => 'AR-TEST001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'currency' => 'MXN',
            'subtotal' => 99.99,
            'tax_amount' => 15.99,
            'total_amount' => 115.98,
            'paid_amount' => 0.00,
            'status' => 'draft',
            'notes' => 'test description',
            'metadata' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'invoiceNumber',
                    'invoiceDate',
                    'dueDate',
                    'contactId',
                    'currency',
                    'subtotal',
                    'taxAmount',
                    'totalAmount',
                    'paidAmount',
                    'status',
                    'journalEntryId',
                    'notes',
                    'metadata',
                    'isActive',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);
    }

    public function test_tech_user_can_view_ARInvoice_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ARInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ARInvoice(): void
    {
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ARInvoice(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
