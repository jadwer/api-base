<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceStoreTest extends TestCase
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

    public function test_admin_can_create_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-AP-001',
                'invoiceDate' => '2024-01-01',
                'dueDate' => '2024-01-31',
                'supplierId' => 1, // Dummy supplier ID (Purchase module not implemented yet)
                'currency' => 'USD',
                'subtotal' => 100.00,
                'taxAmount' => 16.00,
                'totalAmount' => 116.00,
                'paidAmount' => 0.00,
                'status' => 'pending',
                'notes' => 'Test AP invoice',
                'metadata' => ['test' => 'value'],
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertCreated();

        $this->assertDatabaseHas('ap_invoices', [
            'invoice_number' => 'INV-AP-001',
            'supplier_id' => 1,
            'currency' => 'USD',
            'status' => 'pending',
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_APInvoice_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-MIN-001',
                'invoiceDate' => '2024-01-01',
                'dueDate' => '2024-01-31',
                'supplierId' => 1,
                'subtotal' => 100.00,
                'taxAmount' => 16.00,
                'totalAmount' => 116.00,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_APInvoice(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-UNAUTH',
                'supplierId' => 1, // Dummy supplier ID
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_APInvoice(): void
    {
        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'invoiceNumber' => 'INV-GUEST',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertStatus(401);
    }

    public function test_cannot_create_APInvoice_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'notes' => 'Missing required fields'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertStatus(422);
    }

    public function test_cannot_create_APInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ap-invoices',
            'attributes' => [
                'invoiceNumber' => '',
                'isActive' => 'not_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData($data)
            ->post('/api/v1/ap-invoices');

        $response->assertStatus(422);
    }
}
