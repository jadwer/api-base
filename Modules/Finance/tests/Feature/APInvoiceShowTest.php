<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceShowTest extends TestCase
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

    public function test_admin_can_view_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

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

    public function test_admin_can_view_APInvoice_with_specific_data(): void
    {
        $admin = $this->getAdminUser();

        $aPInvoice = APInvoice::factory()->create([
            'invoice_number' => 'AP-TEST001',
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
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

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

    public function test_tech_user_can_view_APInvoice_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_APInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_APInvoice(): void
    {
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get("/api/v1/ap-invoices/{$aPInvoice->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
