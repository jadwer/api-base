<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodIndexTest extends TestCase
{
    public function test_admin_can_list_shipping_methods(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'carrier',
                        'baseCost',
                        'costPerKg',
                        'estimatedDaysMin',
                        'estimatedDaysMax',
                        'is_active',
                        'availableCountries',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_shipping_methods_by_name(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->create(['name' => 'Express Shipping']);
        ShippingMethod::factory()->create(['name' => 'Standard Shipping']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_shipping_methods_by_carrier(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->create(['carrier' => 'DHL']);
        ShippingMethod::factory()->create(['carrier' => 'FedEx']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods?filter[carrier]=DHL');

        $response->assertOk();
    }

    public function test_admin_can_filter_active_shipping_methods(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->create(['is_active' => true]);
        ShippingMethod::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods?filter[isActive]=true');

        $response->assertOk();
    }

    public function test_tech_user_can_list_shipping_methods(): void
    {
        $tech = $this->getTechUser();
        ShippingMethod::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods');

        $response->assertOk();
    }

    public function test_customer_can_list_active_shipping_methods(): void
    {
        $customer = $this->getCustomerUser();
        ShippingMethod::factory()->create(['is_active' => true]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods');

        $response->assertOk();
    }

    public function test_guest_cannot_list_shipping_methods(): void
    {
        ShippingMethod::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods');

        $response->assertStatus(401);
    }

    public function test_pagination_works_for_shipping_methods(): void
    {
        $admin = $this->getAdminUser();
        ShippingMethod::factory()->count(20)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shipping-methods')
            ->get('/api/v1/shipping-methods?page[number]=1&page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
    }
}
