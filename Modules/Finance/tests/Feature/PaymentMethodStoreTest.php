<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodStoreTest extends TestCase
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

    public function test_admin_can_create_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'requiresReference' => true,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertCreated();
        
        $this->assertDatabaseHas('payment_methods', ['code' => 'TEST123', 'name' => 'Test Name', 'requires_reference' => true, 'isActive' => true]);
    }

    public function test_admin_can_create_PaymentMethod_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'requiresReference' => true,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_PaymentMethod(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'name' => 'Unauthorized PaymentMethod',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_PaymentMethod(): void
    {
        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'name' => 'Guest PaymentMethod',
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertStatus(401);
    }

    public function test_cannot_create_PaymentMethod_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'code' => 'MISSING'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertStatus(422);
    }

    public function test_cannot_create_PaymentMethod_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-methods',
            'attributes' => [
                'name' => '', // Empty name
                'isActive' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->post('/api/v1/payment-methods');

        $response->assertStatus(422);
    }
}
