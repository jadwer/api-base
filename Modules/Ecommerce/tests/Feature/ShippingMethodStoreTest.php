<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodStoreTest extends TestCase
{
    public function test_admin_can_create_shipping_method(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'Express Delivery',
                'code' => 'express-001',
                'carrier' => 'DHL',
                'description' => 'Fast delivery service',
                'baseCost' => 199.99,
                'costPerKg' => 25.00,
                'estimatedDaysMin' => 2,
                'estimatedDaysMax' => 4,
                'isActive' => true,
                'availableCountries' => ['MX', 'US'],
                'metadata' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                ],
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertCreated();
        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Express Delivery',
            'code' => 'express-001',
            'carrier' => 'DHL',
            'isActive' => true,
        ]);
    }

    public function test_admin_can_create_free_shipping_method(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'Free Shipping',
                'code' => 'free-standard',
                'carrier' => 'Estafeta',
                'baseCost' => 0.00,
                'costPerKg' => 0.00,
                'estimatedDaysMin' => 7,
                'estimatedDaysMax' => 15,
                'isActive' => true,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertCreated();
        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Free Shipping',
            'base_cost' => 0.00,
        ]);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'Incomplete Method',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertStatus(422);
    }

    public function test_validation_requires_unique_code(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->create(['code' => 'duplicate-code']);

        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'New Method',
                'code' => 'duplicate-code',
                'carrier' => 'FedEx',
                'baseCost' => 150.00,
                'estimatedDaysMin' => 3,
                'estimatedDaysMax' => 5,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertStatus(422);
    }

    public function test_customer_cannot_create_shipping_method(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'Customer Method',
                'code' => 'customer-001',
                'carrier' => 'UPS',
                'baseCost' => 100.00,
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_shipping_method(): void
    {
        $data = [
            'type' => 'shipping-methods',
            'attributes' => [
                'name' => 'Guest Method',
                'code' => 'guest-001',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('shipping-methods')
            ->withData($data)
            ->post('/api/v1/shipping-methods');

        $response->assertStatus(401);
    }
}
