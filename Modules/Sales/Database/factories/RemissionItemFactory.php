<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Product\Models\Product;

/**
 * SA-M006: RemissionItem Factory
 */
class RemissionItemFactory extends Factory
{
    protected $model = RemissionItem::class;

    public function definition(): array
    {
        return [
            'remission_id' => Remission::factory(),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'product_name' => $this->faker->words(3, true),
            'product_sku' => strtoupper($this->faker->bothify('???-####')),
            'unit' => 'PZA',
            'notes' => null,
            'batch_number' => null,
            'expiry_date' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set a specific remission.
     */
    public function forRemission(Remission $remission): static
    {
        return $this->state(fn (array $attributes) => [
            'remission_id' => $remission->id,
        ]);
    }

    /**
     * Set batch tracking information.
     */
    public function withBatch(string $batchNumber, ?\DateTime $expiryDate = null): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate ?? now()->addYear(),
        ]);
    }
}
