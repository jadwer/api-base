<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        $carriers = ['DHL', 'FedEx', 'UPS', 'Estafeta', 'Redpack', '99 Minutos'];
        $carrier = $this->faker->randomElement($carriers);

        return [
            'name' => $this->faker->randomElement([
                'Standard Shipping',
                'Express Shipping',
                'Overnight Delivery',
                'Same Day Delivery',
                'Economy Shipping',
                'Priority Mail',
            ]),
            'code' => strtolower($this->faker->unique()->lexify('???-###')),
            'carrier' => $carrier,
            'description' => $this->faker->optional()->sentence(),
            'base_cost' => $this->faker->randomFloat(2, 50, 200),
            'cost_per_kg' => $this->faker->optional()->randomFloat(2, 10, 50),
            'estimated_days_min' => $this->faker->numberBetween(1, 3),
            'estimated_days_max' => $this->faker->numberBetween(5, 10),
            'is_active' => true,
            'available_countries' => ['MX'],
            'metadata' => [
                'tracking_available' => $this->faker->boolean(80),
                'insurance_available' => $this->faker->boolean(60),
                'max_weight_kg' => $this->faker->randomElement([30, 50, 100, 500]),
            ],
        ];
    }

    /**
     * Indicate that the shipping method is standard/economy.
     */
    public function standard(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Standard Shipping',
                'code' => 'standard',
                'base_cost' => 99.00,
                'cost_per_kg' => 15.00,
                'estimated_days_min' => 5,
                'estimated_days_max' => 10,
                'description' => 'Standard ground shipping with tracking',
            ];
        });
    }

    /**
     * Indicate that the shipping method is express.
     */
    public function express(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Express Shipping',
                'code' => 'express',
                'base_cost' => 199.00,
                'cost_per_kg' => 30.00,
                'estimated_days_min' => 2,
                'estimated_days_max' => 4,
                'description' => 'Express delivery with priority handling',
            ];
        });
    }

    /**
     * Indicate that the shipping method is overnight/same-day.
     */
    public function overnight(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Overnight Delivery',
                'code' => 'overnight',
                'base_cost' => 399.00,
                'cost_per_kg' => 50.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 1,
                'description' => 'Next-day delivery guaranteed',
                'metadata' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                    'max_weight_kg' => 30,
                    'cutoff_time' => '14:00',
                ],
            ];
        });
    }

    /**
     * Indicate that the shipping method is free shipping.
     */
    public function free(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Free Shipping',
                'code' => 'free',
                'base_cost' => 0.00,
                'cost_per_kg' => 0.00,
                'estimated_days_min' => 7,
                'estimated_days_max' => 15,
                'description' => 'Free standard shipping for orders over $999',
            ];
        });
    }

    /**
     * Indicate that the shipping method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the shipping method is for international shipping.
     */
    public function international(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'International Shipping',
                'code' => 'international',
                'base_cost' => 599.00,
                'cost_per_kg' => 80.00,
                'estimated_days_min' => 10,
                'estimated_days_max' => 20,
                'available_countries' => ['MX', 'US', 'CA', 'ES', 'AR', 'CO', 'CL'],
                'description' => 'International shipping with customs handling',
                'metadata' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                    'max_weight_kg' => 100,
                    'customs_included' => true,
                ],
            ];
        });
    }

    /**
     * Indicate that the shipping method uses a specific carrier.
     */
    public function carrier(string $carrierName): static
    {
        return $this->state(function (array $attributes) use ($carrierName) {
            return [
                'carrier' => $carrierName,
            ];
        });
    }

    /**
     * Indicate that the shipping method is for DHL.
     */
    public function dhl(): static
    {
        return $this->carrier('DHL');
    }

    /**
     * Indicate that the shipping method is for FedEx.
     */
    public function fedex(): static
    {
        return $this->carrier('FedEx');
    }

    /**
     * Indicate that the shipping method is for UPS.
     */
    public function ups(): static
    {
        return $this->carrier('UPS');
    }

    /**
     * Indicate that the shipping method is for Estafeta (Mexican carrier).
     */
    public function estafeta(): static
    {
        return $this->carrier('Estafeta');
    }

    /**
     * Indicate that the shipping method is available in multiple countries.
     */
    public function multiCountry(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'available_countries' => ['MX', 'US', 'CA'],
            ];
        });
    }
}
