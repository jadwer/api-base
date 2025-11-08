<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\CheckoutSession;

class CheckoutSessionShowTest extends TestCase
{
    public function test_admin_can_view_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'checkout-sessions',
                'id' => (string) $session->id,
            ]
        ]);
    }

    public function test_admin_can_view_checkout_session_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create([
            'status' => 'initiated',
            'total_amount' => 1500.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'initiated',
                    'totalAmount' => 1500.00,
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_checkout_session_with_permission(): void
    {
        $tech = $this->getTechUser();
        $session = CheckoutSession::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
    }

    public function test_customer_can_only_view_own_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertOk();
    }

    public function test_customer_cannot_view_other_users_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $otherUser = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_checkout_session(): void
    {
        $session = CheckoutSession::factory()->create();

        $response = $this->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_checkout_session(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions/999999');

        $response->assertStatus(404);
    }
}
