<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: Factory for Shipment model.
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered']);
        $shipDate = null;
        $estimatedDelivery = null;
        $actualDelivery = null;

        if (in_array($status, ['shipped', 'delivered'])) {
            $shipDate = $this->faker->dateTimeBetween('-1 month', '-1 week');
            $estimatedDelivery = $this->faker->dateTimeBetween($shipDate, '+1 week');
        }

        if ($status === 'delivered') {
            $actualDelivery = $this->faker->dateTimeBetween($shipDate, 'now');
        }

        return [
            'sales_order_id' => SalesOrder::factory()->state(['status' => 'confirmed']),
            'warehouse_id' => null,
            'shipment_number' => Shipment::generateShipmentNumber(),
            'status' => $status,
            'carrier' => $this->faker->optional(0.8)->randomElement([
                'FedEx', 'UPS', 'DHL', 'USPS', 'Estafeta', 'Paquetexpress', 'RedPack'
            ]),
            'tracking_number' => $this->faker->optional(0.7)->regexify('[A-Z0-9]{12,20}'),
            'tracking_url' => $this->faker->optional(0.5)->url(),
            'ship_date' => $shipDate?->format('Y-m-d'),
            'estimated_delivery' => $estimatedDelivery?->format('Y-m-d'),
            'actual_delivery' => $actualDelivery?->format('Y-m-d'),
            'shipping_address' => $this->faker->address(),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'shipping_cost' => $this->faker->randomFloat(2, 50, 500),
            'weight' => $this->faker->optional(0.6)->randomFloat(2, 0.5, 50),
            'metadata' => $this->faker->optional(0.4)->passthrough([
                'package_count' => $this->faker->numberBetween(1, 5),
                'insurance' => $this->faker->boolean(30),
                'signature_required' => $this->faker->boolean(50),
            ]),
        ];
    }

    /**
     * Shipment in pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'ship_date' => null,
            'estimated_delivery' => null,
            'actual_delivery' => null,
            'tracking_number' => null,
        ]);
    }

    /**
     * Shipment in processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'ship_date' => null,
            'estimated_delivery' => $this->faker->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d'),
            'actual_delivery' => null,
        ]);
    }

    /**
     * Shipment that has been shipped.
     */
    public function shipped(): static
    {
        return $this->state(function (array $attributes) {
            $shipDate = $this->faker->dateTimeBetween('-1 week', 'now');
            return [
                'status' => 'shipped',
                'ship_date' => $shipDate->format('Y-m-d'),
                'estimated_delivery' => $this->faker->dateTimeBetween($shipDate, '+1 week')->format('Y-m-d'),
                'actual_delivery' => null,
                'carrier' => $this->faker->randomElement(['FedEx', 'UPS', 'DHL', 'Estafeta']),
                'tracking_number' => $this->faker->regexify('[A-Z0-9]{15}'),
            ];
        });
    }

    /**
     * Shipment that has been delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            $shipDate = $this->faker->dateTimeBetween('-2 weeks', '-1 week');
            return [
                'status' => 'delivered',
                'ship_date' => $shipDate->format('Y-m-d'),
                'estimated_delivery' => $this->faker->dateTimeBetween($shipDate, '-3 days')->format('Y-m-d'),
                'actual_delivery' => $this->faker->dateTimeBetween('-5 days', 'now')->format('Y-m-d'),
                'carrier' => $this->faker->randomElement(['FedEx', 'UPS', 'DHL', 'Estafeta']),
                'tracking_number' => $this->faker->regexify('[A-Z0-9]{15}'),
            ];
        });
    }

    /**
     * Shipment that has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'ship_date' => null,
            'actual_delivery' => null,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancellation_reason' => $this->faker->randomElement([
                    'Customer request',
                    'Out of stock',
                    'Address not valid',
                    'Duplicate order',
                ]),
            ]),
        ]);
    }

    /**
     * Shipment with a specific warehouse.
     */
    public function withWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => Warehouse::factory(),
        ]);
    }

    /**
     * Shipment with full tracking info.
     */
    public function withTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'carrier' => $this->faker->randomElement(['FedEx', 'UPS', 'DHL']),
            'tracking_number' => $this->faker->regexify('[A-Z0-9]{18}'),
            'tracking_url' => 'https://track.carrier.com/' . $this->faker->regexify('[A-Z0-9]{18}'),
        ]);
    }

    /**
     * Express/priority shipment.
     */
    public function express(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_cost' => $this->faker->randomFloat(2, 200, 800),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'service_type' => 'express',
                'priority' => 'high',
                'guaranteed_delivery' => true,
            ]),
        ]);
    }
}
