<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationStoreTest extends TestCase
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

    public function test_admin_can_create_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'amount' => 99.99,
                'applicationDate' => '2024-01-01',
                'notes' => 'test description',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertCreated();
        
        $this->assertDatabaseHas('payment_applications', ['amount' => 99.99, 'application_date' => 'test value', 'notes' => 'test description', 'is_active' => true]);
    }

    public function test_admin_can_create_PaymentApplication_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_PaymentApplication(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'name' => 'Unauthorized PaymentApplication',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_PaymentApplication(): void
    {
        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'name' => 'Guest PaymentApplication',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertStatus(401);
    }

    public function test_cannot_create_PaymentApplication_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_PaymentApplication_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-applications',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->post('/api/v1/payment-applications');

        $response->assertStatus(422);
    }
}
