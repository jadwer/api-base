<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyFactory extends Factory
{
    protected $model = IdempotencyKey::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 100),
            'user_id' => 1, // TODO: Use existing User ID - \Modules\User\Models\User::inRandomOrder()->first()?->id ?? 1,
            'endpoint' => $this->faker->sentence(3),
            'idempotency_key' => $this->faker->sentence(3),
            'request_hash' => $this->faker->sentence(3),
            'response_data' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+90 days'),
        ];
    }

    /**
     * Active IdempotencyKey
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive IdempotencyKey
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
