<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodShowTest extends TestCase
{
    public function test_admin_can_view_shipping_method(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'shipping-methods',
                'id' => (string) $method->id,
                'attributes' => [
                    'name' => $method->name,
                    'code' => $method->code,
                    'carrier' => $method->carrier,
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_shipping_method(): void
    {
        $tech = $this->getTechUser();
        $method = ShippingMethod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
    }

    public function test_customer_can_view_active_shipping_method(): void
    {
        $customer = $this->getCustomerUser();
        $method = ShippingMethod::factory()->create(['is_active' => true]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
    }

    public function test_shipping_method_includes_estimated_delivery_attribute(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create([
            'estimated_days_min' => 3,
            'estimated_days_max' => 5,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'estimatedDelivery' => '3-5 días',
                ]
            ]
        ]);
    }

    public function test_guest_cannot_view_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create();

        $response = $this->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_shipping_method(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods/999999');

        $response->assertStatus(404);
    }
}
