<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodDestroyTest extends TestCase
{
    public function test_admin_can_delete_shipping_method(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->delete('/api/v1/shipping-methods/' . $method->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);
    }

    public function test_admin_cannot_delete_shipping_method_with_active_checkout_sessions(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create();

        // Create a checkout session that uses this shipping method
        \Modules\Ecommerce\Models\CheckoutSession::factory()->create([
            'shippingMethodId' => $method->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->delete('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(409); // Conflict - cannot delete due to relationships
        $this->assertDatabaseHas('shipping_methods', ['id' => $method->id]);
    }

    public function test_customer_cannot_delete_shipping_method(): void
    {
        $customer = $this->getCustomerUser();
        $method = ShippingMethod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->delete('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('shipping_methods', ['id' => $method->id]);
    }

    public function test_guest_cannot_delete_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create();

        $response = $this->jsonApi()
            ->expects('shipping-methods')
            ->delete('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('shipping_methods', ['id' => $method->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_shipping_method(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->delete('/api/v1/shipping-methods/999999');

        $response->assertStatus(404);
    }
}
