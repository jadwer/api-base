<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceStoreTest extends TestCase
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

    public function test_admin_can_create_ARInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-AR-001',
                'invoiceDate' => '2024-01-01',
                'dueDate' => '2024-01-31',
                'customerId' => 1, // Dummy customer ID (Sales module)
                'currency' => 'USD',
                'subtotal' => 100.00,
                'taxAmount' => 16.00,
                'totalAmount' => 116.00,
                'paidAmount' => 0.00,
                'status' => 'pending',
                'notes' => 'Test AR invoice',
                'metadata' => ['test' => 'value'],
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertCreated();

        $this->assertDatabaseHas('ar_invoices', [
            'invoice_number' => 'INV-AR-001',
            'customer_id' => 1,
            'currency' => 'USD',
            'status' => 'pending',
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_ARInvoice_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-MIN-AR-001',
                'invoiceDate' => '2024-01-01',
                'dueDate' => '2024-01-31',
                'customerId' => 1,
                'subtotal' => 100.00,
                'taxAmount' => 16.00,
                'totalAmount' => 116.00,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ARInvoice(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-UNAUTH',
                'customerId' => 1, // Dummy customer ID
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ARInvoice(): void
    {
        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-GUEST',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ARInvoice_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'notes' => 'Missing required fields'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertStatus(422);
    }

    public function test_cannot_create_ARInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceNumber' => '',
                'isActive' => 'not_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertStatus(422);
    }
}
