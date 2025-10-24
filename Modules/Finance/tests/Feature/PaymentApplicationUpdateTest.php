<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationUpdateTest extends TestCase
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

    public function test_admin_can_update_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'name' => 'Updated PaymentApplication',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payment_applications', [
            'id' => $paymentApplication->id,
            'name' => 'Updated PaymentApplication',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payment_applications', [
            'id' => $paymentApplication->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_PaymentApplication_metadata(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        
        $paymentApplication->refresh();
        $this->assertEquals($metadata, $paymentApplication->metadata);
    }

    public function test_customer_user_cannot_update_PaymentApplication(): void
    {
        $customer = $this->getCustomerUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_PaymentApplication(): void
    {
        $paymentApplication = PaymentApplication::factory()->create();

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payment-applications',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch('/api/v1/payment-applications/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_PaymentApplication_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $data = [
            'type' => 'payment-applications',
            'id' => (string) $paymentApplication->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->withData($data)
            ->patch("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(422);
    }
}
