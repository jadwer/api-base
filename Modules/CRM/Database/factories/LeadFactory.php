<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'source' => $this->faker->randomElement(['website', 'referral', 'cold call', 'trade show', 'social media', 'email campaign']),
            'status' => 'new',
            'rating' => 'warm',
            'contact_id' => null, // Will be populated when Contact module is implemented
            'user_id' => User::factory(),
            'company_name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'estimated_value' => $this->faker->randomFloat(2, 1000, 500000),
            'estimated_close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'converted_at' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'metadata' => [
                'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Services']),
                'company_size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
                'budget_range' => $this->faker->randomElement(['< $10k', '$10k-$50k', '$50k-$100k', '$100k+']),
            ],
        ];
    }

    /**
     * Status States
     */
    public function statusNew(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
            'converted_at' => null,
        ]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'contacted',
            'converted_at' => null,
        ]);
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qualified',
            'rating' => $this->faker->randomElement(['hot', 'warm']),
            'converted_at' => null,
        ]);
    }

    public function unqualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unqualified',
            'rating' => 'cold',
            'estimated_value' => null,
            'estimated_close_date' => null,
            'converted_at' => null,
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'rating' => 'hot',
            'converted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Rating States
     */
    public function hot(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 'hot',
            'estimated_value' => $this->faker->randomFloat(2, 50000, 500000),
            'estimated_close_date' => $this->faker->dateTimeBetween('now', '+3 months'),
        ]);
    }

    public function warm(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 'warm',
            'estimated_value' => $this->faker->randomFloat(2, 10000, 100000),
            'estimated_close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ]);
    }

    public function cold(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 'cold',
            'estimated_value' => $this->faker->randomFloat(2, 1000, 25000),
            'estimated_close_date' => $this->faker->dateTimeBetween('now', '+12 months'),
        ]);
    }

    /**
     * Contact State
     */
    public function withoutContact(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_id' => null,
        ]);
    }

    /**
     * Value State
     */
    public function withoutEstimatedValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_value' => null,
            'estimated_close_date' => null,
        ]);
    }

    /**
     * Source States
     */
    public function fromWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'website',
        ]);
    }

    public function fromReferral(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'referral',
        ]);
    }

    public function fromColdCall(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'cold call',
        ]);
    }

    /**
     * Assignment State
     */
    public function assignedTo(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
