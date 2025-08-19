<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceReceipt;

class ARInvoiceReceiptStoreTest extends TestCase
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

    public function test_admin_can_create_ARInvoiceReceipt(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [
                'amountApplied' => 99.99,
                'appliedAt' => '2024-01-01',
                'exchangeRateAtApply' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('ar_invoice_receipts', ['amount_applied' => 99.99, 'applied_at' => 'test value', 'exchange_rate_at_apply' => 99.99]);
    }

    public function test_admin_can_create_ARInvoiceReceipt_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ARInvoiceReceipt(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [
                'name' => 'Unauthorized ARInvoiceReceipt',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ARInvoiceReceipt(): void
    {
        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [
                'name' => 'Guest ARInvoiceReceipt',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ARInvoiceReceipt_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ARInvoiceReceipt_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-receipts',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(422);
    }
}
