<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APPayment;

class APPaymentStoreTest extends TestCase
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

    public function test_admin_can_create_APPayment(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-payments',
            'attributes' => [
                'paymentDate' => '2024-01-01',
                'paymentMethod' => 'test string',
                'currency' => 'test string',
                'amount' => 99.99,
                'status' => 'active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertCreated();
        
        $this->assertDatabaseHas('ap_payments', ['payment_date' => 'test value', 'payment_method' => 'test string', 'currency' => 'test string', 'amount' => 99.99, 'status' => 'active']);
    }

    public function test_admin_can_create_APPayment_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-payments',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_APPayment(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'a-p-payments',
            'attributes' => [
                'name' => 'Unauthorized APPayment',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_APPayment(): void
    {
        $data = [
            'type' => 'a-p-payments',
            'attributes' => [
                'name' => 'Guest APPayment',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertStatus(401);
    }

    public function test_cannot_create_APPayment_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-payments',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_APPayment_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-payments',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->post('/api/v1/a-p-payments');

        $response->assertStatus(422);
    }
}
