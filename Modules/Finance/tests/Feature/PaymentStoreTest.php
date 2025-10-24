<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\Payment;

class PaymentStoreTest extends TestCase
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

    public function test_admin_can_create_Payment(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payments',
            'attributes' => [
                'paymentNumber' => 'test string',
                'paymentDate' => '2024-01-01',
                'amount' => 99.99,
                'currency' => 'test string',
                'appliedAmount' => 99.99,
                'unappliedAmount' => 99.99,
                'status' => 'active',
                'reference' => 'test string',
                'notes' => 'test description',
                'metadata' => 'test value',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertCreated();
        
        $this->assertDatabaseHas('payments', ['payment_number' => 'test string', 'payment_date' => 'test value', 'amount' => 99.99, 'currency' => 'test string', 'applied_amount' => 99.99, 'unapplied_amount' => 99.99, 'status' => 'active', 'reference' => 'test string', 'notes' => 'test description', 'metadata' => 'test value', 'is_active' => true]);
    }

    public function test_admin_can_create_Payment_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payments',
            'attributes' => [
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Payment(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'payments',
            'attributes' => [
                'name' => 'Unauthorized Payment',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Payment(): void
    {
        $data = [
            'type' => 'payments',
            'attributes' => [
                'name' => 'Guest Payment',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Payment_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payments',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_Payment_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payments',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->post('/api/v1/payments');

        $response->assertStatus(422);
    }
}
