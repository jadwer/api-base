<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyFactory extends Factory
{
    protected $model = IdempotencyKey::class;

    public function definition(): array
    {
        $endpoints = [
            'POST /api/v1/journal-entries',
            'PATCH /api/v1/journal-entries/{id}',
            'POST /api/v1/accounts',
            'POST /api/v1/exchange-rates',
            'POST /api/v1/fiscal-periods'
        ];

        $responseData = [
            'id' => $this->faker->numberBetween(1, 1000),
            'type' => 'journal-entries',
            'attributes' => [
                'number' => $this->faker->bothify('JE-####'),
                'status' => 'draft'
            ]
        ];

        return [
            'company_id' => null,
            'user_id' => \Modules\User\Models\User::factory(),
            'endpoint' => $this->faker->randomElement($endpoints),
            'idempotency_key' => $this->faker->uuid(),
            'request_hash' => $this->faker->sha256(),
            'response_data' => $responseData,
            'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'metadata' => [],
        ];
    }

    /**
     * Processing IdempotencyKey
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'response_data' => null,
        ]);
    }

    /**
     * Completed IdempotencyKey
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
