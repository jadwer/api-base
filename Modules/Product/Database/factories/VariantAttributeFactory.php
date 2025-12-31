<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\VariantAttribute;

/**
 * PR-M003: Factory for VariantAttribute model.
 */
class VariantAttributeFactory extends Factory
{
    protected $model = VariantAttribute::class;

    /**
     * Common attribute types with their codes.
     */
    protected array $attributeTypes = [
        ['name' => 'Size', 'code' => 'size'],
        ['name' => 'Color', 'code' => 'color'],
        ['name' => 'Material', 'code' => 'material'],
        ['name' => 'Style', 'code' => 'style'],
        ['name' => 'Weight', 'code' => 'weight'],
        ['name' => 'Length', 'code' => 'length'],
        ['name' => 'Width', 'code' => 'width'],
        ['name' => 'Capacity', 'code' => 'capacity'],
        ['name' => 'Flavor', 'code' => 'flavor'],
        ['name' => 'Scent', 'code' => 'scent'],
    ];

    public function definition(): array
    {
        $attribute = $this->faker->unique()->randomElement($this->attributeTypes);

        return [
            'name' => $attribute['name'],
            'code' => $attribute['code'],
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Size attribute.
     */
    public function size(): static
    {
        return $this->state(fn() => [
            'name' => 'Size',
            'code' => 'size',
            'description' => 'Product size variations',
        ]);
    }

    /**
     * Color attribute.
     */
    public function color(): static
    {
        return $this->state(fn() => [
            'name' => 'Color',
            'code' => 'color',
            'description' => 'Product color variations',
        ]);
    }

    /**
     * Material attribute.
     */
    public function material(): static
    {
        return $this->state(fn() => [
            'name' => 'Material',
            'code' => 'material',
            'description' => 'Product material variations',
        ]);
    }

    /**
     * Inactive attribute.
     */
    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
