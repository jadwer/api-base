<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceLine;

class ARInvoiceLineStoreTest extends TestCase
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

    public function test_admin_can_create_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [
                'description' => 'test string',
                'quantity' => 99.99,
                'unitPrice' => 99.99,
                'discount' => 99.99,
                'lineTotal' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertCreated();
        
        $this->assertDatabaseHas('ar_invoice_lines', ['description' => 'test string', 'quantity' => 99.99, 'unit_price' => 99.99, 'discount' => 99.99, 'line_total' => 99.99]);
    }

    public function test_admin_can_create_ARInvoiceLine_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ARInvoiceLine(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [
                'name' => 'Unauthorized ARInvoiceLine',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ARInvoiceLine(): void
    {
        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [
                'name' => 'Guest ARInvoiceLine',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ARInvoiceLine_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ARInvoiceLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-lines');

        $response->assertStatus(422);
    }
}
