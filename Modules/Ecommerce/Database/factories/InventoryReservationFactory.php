<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\InventoryReservation;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\Stock;

class InventoryReservationFactory extends Factory
{
    protected $model = InventoryReservation::class;

    public function definition(): array
    {
        return [
            'checkout_session_id' => CheckoutSession::factory(),
            'stock_id' => Stock::factory(),
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity_reserved' => $this->faker->numberBetween(1, 10),
            'status' => 'active',
            'expires_at' => now()->addHours(2),
            'released_at' => null,
            'fulfilled_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the reservation is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'expires_at' => now()->addHours($this->faker->numberBetween(1, 24)),
                'released_at' => null,
                'fulfilled_at' => null,
            ];
        });
    }

    /**
     * Indicate that the reservation is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expired',
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
                'released_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'fulfilled_at' => null,
                'notes' => 'Reservation expired automatically',
            ];
        });
    }

    /**
     * Indicate that the reservation was released.
     */
    public function released(): static
    {
        return $this->state(function (array $attributes) {
            $releasedAt = $this->faker->dateTimeBetween('-1 month', 'now');

            return [
                'status' => 'released',
                'expires_at' => $this->faker->dateTimeBetween('-1 month', $releasedAt),
                'released_at' => $releasedAt,
                'fulfilled_at' => null,
                'notes' => $this->faker->randomElement([
                    'Customer cancelled order',
                    'Payment failed',
                    'Product unavailable',
                    'Manual release by admin',
                    'Checkout session expired',
                ]),
            ];
        });
    }

    /**
     * Indicate that the reservation was fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(function (array $attributes) {
            $fulfilledAt = $this->faker->dateTimeBetween('-1 month', 'now');

            return [
                'status' => 'fulfilled',
                'expires_at' => $this->faker->dateTimeBetween('-1 month', $fulfilledAt),
                'released_at' => null,
                'fulfilled_at' => $fulfilledAt,
                'notes' => 'Successfully fulfilled with order completion',
            ];
        });
    }

    /**
     * Indicate that the reservation is about to expire soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'expires_at' => now()->addMinutes($this->faker->numberBetween(5, 30)),
            ];
        });
    }

    /**
     * Indicate that the reservation is for a large quantity.
     */
    public function largeQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'quantity_reserved' => $this->faker->numberBetween(50, 500),
                'notes' => 'Large quantity reservation - requires special handling',
            ];
        });
    }

    /**
     * Indicate that the reservation is for a specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(function (array $attributes) use ($productId) {
            return [
                'product_id' => $productId,
            ];
        });
    }

    /**
     * Indicate that the reservation is for a specific warehouse.
     */
    public function forWarehouse(int $warehouseId): static
    {
        return $this->state(function (array $attributes) use ($warehouseId) {
            return [
                'warehouse_id' => $warehouseId,
            ];
        });
    }

    /**
     * Indicate that the reservation is for a specific checkout session.
     */
    public function forCheckoutSession(int $checkoutSessionId): static
    {
        return $this->state(function (array $attributes) use ($checkoutSessionId) {
            return [
                'checkout_session_id' => $checkoutSessionId,
            ];
        });
    }
}
