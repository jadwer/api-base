<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodUpdateTest extends TestCase
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

    public function test_admin_can_update_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'name' => 'Updated PaymentMethod',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Updated PaymentMethod',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_PaymentMethod_metadata(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        
        $paymentMethod->refresh();
        $this->assertEquals($metadata, $paymentMethod->metadata);
    }

    public function test_customer_user_cannot_update_PaymentMethod(): void
    {
        $customer = $this->getCustomerUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_PaymentMethod(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-methods',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch('/api/v1/payment-methods/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_PaymentMethod_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $data = [
            'type' => 'payment-methods',
            'id' => (string) $paymentMethod->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->withData($data)
            ->patch("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(422);
    }
}
