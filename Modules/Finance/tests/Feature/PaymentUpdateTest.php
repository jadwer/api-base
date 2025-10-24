<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\Payment;

class PaymentUpdateTest extends TestCase
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

    public function test_admin_can_update_Payment(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'name' => 'Updated Payment',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'name' => 'Updated Payment',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_Payment(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_Payment_metadata(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        
        $payment->refresh();
        $this->assertEquals($metadata, $payment->metadata);
    }

    public function test_customer_user_cannot_update_Payment(): void
    {
        $customer = $this->getCustomerUser();
        $payment = Payment::factory()->create();

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_Payment(): void
    {
        $payment = Payment::factory()->create();

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Payment(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payments',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch('/api/v1/payments/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_Payment_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();

        $data = [
            'type' => 'payments',
            'id' => (string) $payment->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData($data)
            ->patch("/api/v1/payments/{$payment->id}");

        $response->assertStatus(422);
    }
}
