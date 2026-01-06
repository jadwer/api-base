<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleFactory extends Factory
{
    protected $model = DiscountRule::class;

    public function definition(): array
    {
        $discountType = $this->faker->randomElement([
            DiscountRule::TYPE_PERCENTAGE,
            DiscountRule::TYPE_FIXED_AMOUNT,
        ]);

        return [
            'name' => $this->faker->words(3, true) . ' Discount',
            'code' => strtoupper($this->faker->unique()->lexify('????') . $this->faker->numerify('##')),
            'description' => $this->faker->optional()->sentence(),
            'discount_type' => $discountType,
            'discount_value' => $discountType === DiscountRule::TYPE_PERCENTAGE
                ? $this->faker->randomFloat(2, 5, 50)
                : $this->faker->randomFloat(2, 10, 500),
            'applies_to' => DiscountRule::APPLIES_TO_ORDER,
            'min_order_amount' => $this->faker->optional(0.5)->randomFloat(2, 100, 1000),
            'min_quantity' => $this->faker->optional(0.3)->numberBetween(1, 10),
            'max_discount_amount' => $this->faker->optional(0.5)->randomFloat(2, 50, 500),
            'start_date' => now()->subDays($this->faker->numberBetween(0, 30)),
            'end_date' => now()->addDays($this->faker->numberBetween(30, 180)),
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(10, 1000),
            'usage_per_customer' => $this->faker->optional(0.3)->numberBetween(1, 5),
            'current_usage' => 0,
            'priority' => $this->faker->numberBetween(0, 100),
            'is_combinable' => $this->faker->boolean(70),
            'is_active' => true,
        ];
    }

    /**
     * Percentage discount state.
     */
    public function percentage(float $value = 10.0): static
    {
        return $this->state(fn () => [
            'discount_type' => DiscountRule::TYPE_PERCENTAGE,
            'discount_value' => $value,
        ]);
    }

    /**
     * Fixed amount discount state.
     */
    public function fixedAmount(float $value = 50.0): static
    {
        return $this->state(fn () => [
            'discount_type' => DiscountRule::TYPE_FIXED_AMOUNT,
            'discount_value' => $value,
        ]);
    }

    /**
     * Buy X Get Y discount state.
     */
    public function buyXGetY(int $buy = 2, int $get = 1): static
    {
        return $this->state(fn () => [
            'discount_type' => DiscountRule::TYPE_BUY_X_GET_Y,
            'buy_quantity' => $buy,
            'get_quantity' => $get,
            'discount_value' => 100, // 100% off for free items
        ]);
    }

    /**
     * Product-specific discount state.
     */
    public function forProduct(array $productIds = []): static
    {
        return $this->state(fn () => [
            'applies_to' => DiscountRule::APPLIES_TO_PRODUCT,
            'product_ids' => $productIds,
        ]);
    }

    /**
     * Category-specific discount state.
     */
    public function forCategory(array $categoryIds = []): static
    {
        return $this->state(fn () => [
            'applies_to' => DiscountRule::APPLIES_TO_CATEGORY,
            'category_ids' => $categoryIds,
        ]);
    }

    /**
     * Customer-specific discount state.
     */
    public function forCustomer(array $customerIds = []): static
    {
        return $this->state(fn () => [
            'applies_to' => DiscountRule::APPLIES_TO_CUSTOMER,
            'customer_ids' => $customerIds,
        ]);
    }

    /**
     * Expired discount state.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(1),
        ]);
    }

    /**
     * Future discount state (not yet active).
     */
    public function future(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(60),
        ]);
    }

    /**
     * Inactive discount state.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * High priority discount state.
     */
    public function highPriority(): static
    {
        return $this->state(fn () => [
            'priority' => 100,
            'is_combinable' => false,
        ]);
    }

    /**
     * Usage limit reached state.
     */
    public function exhausted(): static
    {
        return $this->state(fn () => [
            'usage_limit' => 10,
            'current_usage' => 10,
        ]);
    }

    /**
     * No expiration date state.
     */
    public function noExpiration(): static
    {
        return $this->state(fn () => [
            'end_date' => null,
        ]);
    }

    /**
     * With minimum order amount state.
     */
    public function withMinimum(float $amount): static
    {
        return $this->state(fn () => [
            'min_order_amount' => $amount,
        ]);
    }
}
