<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\CheckoutSession;

class CheckoutSessionUpdateTest extends TestCase
{
    public function test_admin_can_update_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['status' => 'initiated']);

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'status' => 'payment_pending',
                'step' => 'payment',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'payment_pending',
            'step' => 'payment',
        ]);
    }

    public function test_admin_can_partially_update_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create([
            'status' => 'initiated',
            'contact_email' => 'old@example.com',
        ]);

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'contactEmail' => 'new@example.com',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'initiated', // unchanged
            'contact_email' => 'new@example.com', // changed
        ]);
    }

    public function test_customer_can_update_own_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create([
            'user_id' => $customer->id,
            'status' => 'initiated',
        ]);

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'step' => 'shipping',
                'contactPhone' => '9876543210',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
    }

    public function test_customer_cannot_update_other_users_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $otherUser = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'status' => 'cancelled',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(403);
    }

    public function test_cannot_update_completed_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['status' => 'completed']);

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'status' => 'initiated',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_checkout_session(): void
    {
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'checkout-sessions',
            'id' => (string) $session->id,
            'attributes' => [
                'status' => 'cancelled',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_checkout_session(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'checkout-sessions',
            'id' => '999999',
            'attributes' => [
                'status' => 'completed',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->patch('/api/v1/checkout-sessions/999999');

        $response->assertStatus(404);
    }
}
