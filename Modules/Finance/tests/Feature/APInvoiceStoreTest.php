<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceStoreTest extends TestCase
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

    public function test_admin_can_create_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'invoiceNumber' => 'test string',
                'invoiceDate' => '2024-01-01',
                'dueDate' => '2024-01-01',
                'currency' => 'test string',
                'subtotal' => 99.99,
                'taxAmount' => 99.99,
                'totalAmount' => 99.99,
                'paidAmount' => 99.99,
                'status' => 'active',
                'notes' => 'test description',
                'metadata' => 'test value',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertCreated();
        
        $this->assertDatabaseHas('ap_invoices', ['invoice_number' => 'test string', 'invoice_date' => 'test value', 'due_date' => 'test value', 'currency' => 'test string', 'subtotal' => 99.99, 'tax_amount' => 99.99, 'total_amount' => 99.99, 'paid_amount' => 99.99, 'status' => 'active', 'notes' => 'test description', 'metadata' => 'test value', 'is_active' => true]);
    }

    public function test_admin_can_create_APInvoice_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_APInvoice(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'name' => 'Unauthorized APInvoice',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_APInvoice(): void
    {
        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'name' => 'Guest APInvoice',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertStatus(401);
    }

    public function test_cannot_create_APInvoice_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_APInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoices',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->post('/api/v1/a-p-invoices');

        $response->assertStatus(422);
    }
}
