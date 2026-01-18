<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 50, 5000);
        $quantity = $this->faker->numberBetween(1, 10);

        return [
            'quote_id' => Quote::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'quoted_price' => $unitPrice, // Same as unit price by default
            'discount_percentage' => 0,
            'tax_rate' => 16, // Default IVA in Mexico
            'product_name' => $this->faker->words(3, true),
            'product_sku' => strtoupper($this->faker->unique()->bothify('???-####')),
        ];
    }

    public function withDiscount(float $percentage = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }

    public function withCustomPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'quoted_price' => $price,
        ]);
    }
}
