<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARReceipt;

class ARReceiptStoreTest extends TestCase
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

    public function test_admin_can_create_ARReceipt(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [
                'receiptDate' => '2024-01-01',
                'paymentMethod' => 'test string',
                'currency' => 'test string',
                'amount' => 99.99,
                'status' => 'active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('ar_receipts', ['receipt_date' => 'test value', 'payment_method' => 'test string', 'currency' => 'test string', 'amount' => 99.99, 'status' => 'active']);
    }

    public function test_admin_can_create_ARReceipt_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ARReceipt(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [
                'name' => 'Unauthorized ARReceipt',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ARReceipt(): void
    {
        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [
                'name' => 'Guest ARReceipt',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ARReceipt_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ARReceipt_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-receipts',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->post('/api/v1/a-r-receipts');

        $response->assertStatus(422);
    }
}
