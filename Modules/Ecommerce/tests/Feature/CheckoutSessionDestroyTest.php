<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\CheckoutSession;

class CheckoutSessionDestroyTest extends TestCase
{
    public function test_admin_can_delete_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('checkout_sessions', ['id' => $session->id]);
    }

    public function test_admin_can_delete_incomplete_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['status' => 'initiated']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('checkout_sessions', ['id' => $session->id]);
    }

    public function test_customer_can_delete_own_incomplete_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create([
            'userId' => $customer->id,
            'status' => 'initiated',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('checkout_sessions', ['id' => $session->id]);
    }

    public function test_customer_cannot_delete_completed_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create([
            'userId' => $customer->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('checkout_sessions', ['id' => $session->id]);
    }

    public function test_customer_cannot_delete_other_users_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $otherUser = $this->getAdminUser();
        $session = CheckoutSession::factory()->create(['userId' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('checkout_sessions', ['id' => $session->id]);
    }

    public function test_guest_cannot_delete_checkout_session(): void
    {
        $session = CheckoutSession::factory()->create();

        $response = $this->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('checkout_sessions', ['id' => $session->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_checkout_session(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->delete('/api/v1/checkout-sessions/' . $session->id);

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }
}
