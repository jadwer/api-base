<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\VariantAttribute;
use Modules\Product\Models\VariantAttributeValue;

/**
 * PR-M003: Factory for VariantAttributeValue model.
 */
class VariantAttributeValueFactory extends Factory
{
    protected $model = VariantAttributeValue::class;

    /**
     * Predefined values by attribute code.
     */
    protected array $valuesByAttribute = [
        'size' => [
            ['value' => 'Extra Small', 'code' => 'xs', 'display_value' => 'XS'],
            ['value' => 'Small', 'code' => 's', 'display_value' => 'S'],
            ['value' => 'Medium', 'code' => 'm', 'display_value' => 'M'],
            ['value' => 'Large', 'code' => 'l', 'display_value' => 'L'],
            ['value' => 'Extra Large', 'code' => 'xl', 'display_value' => 'XL'],
            ['value' => 'Double Extra Large', 'code' => 'xxl', 'display_value' => 'XXL'],
        ],
        'color' => [
            ['value' => 'Red', 'code' => 'red', 'display_value' => 'Red', 'color_hex' => '#FF0000'],
            ['value' => 'Blue', 'code' => 'blue', 'display_value' => 'Blue', 'color_hex' => '#0000FF'],
            ['value' => 'Green', 'code' => 'green', 'display_value' => 'Green', 'color_hex' => '#00FF00'],
            ['value' => 'Black', 'code' => 'black', 'display_value' => 'Black', 'color_hex' => '#000000'],
            ['value' => 'White', 'code' => 'white', 'display_value' => 'White', 'color_hex' => '#FFFFFF'],
            ['value' => 'Yellow', 'code' => 'yellow', 'display_value' => 'Yellow', 'color_hex' => '#FFFF00'],
            ['value' => 'Orange', 'code' => 'orange', 'display_value' => 'Orange', 'color_hex' => '#FFA500'],
            ['value' => 'Purple', 'code' => 'purple', 'display_value' => 'Purple', 'color_hex' => '#800080'],
        ],
        'material' => [
            ['value' => 'Cotton', 'code' => 'cotton', 'display_value' => 'Cotton'],
            ['value' => 'Polyester', 'code' => 'polyester', 'display_value' => 'Polyester'],
            ['value' => 'Leather', 'code' => 'leather', 'display_value' => 'Leather'],
            ['value' => 'Wool', 'code' => 'wool', 'display_value' => 'Wool'],
            ['value' => 'Silk', 'code' => 'silk', 'display_value' => 'Silk'],
            ['value' => 'Linen', 'code' => 'linen', 'display_value' => 'Linen'],
        ],
    ];

    public function definition(): array
    {
        return [
            'variant_attribute_id' => VariantAttribute::factory(),
            'value' => $this->faker->word(),
            'code' => $this->faker->unique()->slug(1),
            'display_value' => null,
            'color_hex' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Create value for specific attribute type.
     */
    public function forAttribute(VariantAttribute $attribute): static
    {
        $code = $attribute->code;
        $values = $this->valuesByAttribute[$code] ?? null;

        if ($values) {
            $randomValue = $this->faker->randomElement($values);
            return $this->state(fn() => [
                'variant_attribute_id' => $attribute->id,
                'value' => $randomValue['value'],
                'code' => $randomValue['code'],
                'display_value' => $randomValue['display_value'] ?? null,
                'color_hex' => $randomValue['color_hex'] ?? null,
            ]);
        }

        return $this->state(fn() => [
            'variant_attribute_id' => $attribute->id,
        ]);
    }

    /**
     * Size value state.
     */
    public function size(string $size = 'Medium'): static
    {
        $sizeMap = [
            'Extra Small' => ['code' => 'xs', 'display' => 'XS'],
            'Small' => ['code' => 's', 'display' => 'S'],
            'Medium' => ['code' => 'm', 'display' => 'M'],
            'Large' => ['code' => 'l', 'display' => 'L'],
            'Extra Large' => ['code' => 'xl', 'display' => 'XL'],
        ];

        $mapped = $sizeMap[$size] ?? ['code' => strtolower($size), 'display' => $size];

        return $this->state(fn() => [
            'value' => $size,
            'code' => $mapped['code'],
            'display_value' => $mapped['display'],
        ]);
    }

    /**
     * Color value state.
     */
    public function color(string $color = 'Red', string $hex = '#FF0000'): static
    {
        return $this->state(fn() => [
            'value' => $color,
            'code' => strtolower($color),
            'display_value' => $color,
            'color_hex' => $hex,
        ]);
    }

    /**
     * Inactive value.
     */
    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
