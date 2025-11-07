<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodUpdateTest extends TestCase
{
    public function test_admin_can_update_shipping_method(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create([
            'name' => 'Old Name',
            'base_cost' => 100.00,
        ]);

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'name' => 'Updated Name',
                'baseCost' => 150.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $this->assertDatabaseHas('shipping_methods', [
            'id' => $method->id,
            'name' => 'Updated Name',
            'base_cost' => 150.00,
        ]);
    }

    public function test_admin_can_deactivate_shipping_method(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create(['is_active' => true]);

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'is_active' => false,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $this->assertDatabaseHas('shipping_methods', [
            'id' => $method->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_update_available_countries(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create([
            'available_countries' => ['MX'],
        ]);

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'availableCountries' => ['MX', 'US', 'CA'],
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $this->assertDatabaseHas('shipping_methods', [
            'id' => $method->id,
        ]);
    }

    public function test_admin_can_partially_update_shipping_method(): void
    {
        $admin = $this->getAdminUser();
        $method = ShippingMethod::factory()->create([
            'name' => 'Original Name',
            'carrier' => 'DHL',
        ]);

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'carrier' => 'FedEx',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertOk();
        $this->assertDatabaseHas('shipping_methods', [
            'id' => $method->id,
            'name' => 'Original Name',  // Unchanged
            'carrier' => 'FedEx',         // Changed
        ]);
    }

    public function test_customer_cannot_update_shipping_method(): void
    {
        $customer = $this->getCustomerUser();
        $method = ShippingMethod::factory()->create();

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'name' => 'Hacked Name',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create();

        $data = [
            'type' => 'shipping-methods',
            'id' => (string) $method->id,
            'attributes' => [
                'name' => 'Guest Update',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->patch('/api/v1/shipping-methods/' . $method->id);

        $response->assertStatus(401);
    }
}
