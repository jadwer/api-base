<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-3 months', '+1 month');
        $endDate = $this->faker->optional(0.7)->dateTimeBetween($startDate, '+6 months');

        return [
            'name' => $this->faker->words(3, true) . ' Campaign',
            'type' => $this->faker->randomElement(['email', 'social_media', 'event', 'webinar', 'direct_mail', 'telemarketing']),
            'status' => 'planning',
            'user_id' => User::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'budget' => $this->faker->optional(0.8)->randomFloat(2, 5000, 500000),
            'actual_cost' => null,
            'expected_revenue' => $this->faker->optional(0.7)->randomFloat(2, 10000, 1000000),
            'actual_revenue' => null,
            'target_audience' => $this->faker->optional(0.8)->words(5, true),
            'description' => $this->faker->optional(0.9)->paragraph(),
            'metadata' => [
                'platform' => $this->faker->randomElement(['Facebook', 'LinkedIn', 'Google Ads', 'Email', 'Direct Mail', 'Events']),
                'goal' => $this->faker->randomElement(['Lead Generation', 'Brand Awareness', 'Sales', 'Product Launch']),
                'target_leads' => $this->faker->numberBetween(100, 5000),
            ],
        ];
    }

    /**
     * Status States
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
            'actual_cost' => null,
            'actual_revenue' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-2 months', 'now');

            return [
                'status' => 'active',
                'start_date' => $startDate,
                // Regenerate end_date relative to the overridden start_date.
                // The base definition computes end_date from its own start_date,
                // so overriding only start_date can leave end_date earlier than
                // start_date and any later update fails the after_or_equal rule.
                'end_date' => $this->faker->optional(0.7)->dateTimeBetween($startDate, '+6 months'),
                'actual_cost' => $this->faker->optional(0.6)->randomFloat(2, 1000, $attributes['budget'] ?? 50000),
            ];
        });
    }

    public function paused(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-2 months', '-1 week');

            return [
                'status' => 'paused',
                'start_date' => $startDate,
                // Same invariant as active(): keep end_date consistent with the
                // overridden start_date.
                'end_date' => $this->faker->optional(0.7)->dateTimeBetween($startDate, '+6 months'),
                'actual_cost' => $this->faker->optional(0.8)->randomFloat(2, 1000, $attributes['budget'] ?? 50000),
            ];
        });
    }

    public function completed(): static
    {
        $budget = $this->faker->randomFloat(2, 10000, 500000);
        $actualCost = $this->faker->randomFloat(2, $budget * 0.7, $budget * 1.2);
        $expectedRevenue = $this->faker->randomFloat(2, $budget * 1.5, $budget * 5);
        $actualRevenue = $this->faker->randomFloat(2, $expectedRevenue * 0.6, $expectedRevenue * 1.3);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => $this->faker->dateTimeBetween('-6 months', '-2 months'),
            'end_date' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'expected_revenue' => $expectedRevenue,
            'actual_revenue' => $actualRevenue,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'start_date' => $this->faker->dateTimeBetween('-4 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'actual_cost' => $this->faker->optional(0.5)->randomFloat(2, 1000, ($attributes['budget'] ?? 50000) * 0.3),
            'actual_revenue' => null,
        ]);
    }

    /**
     * Type States
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'email',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'platform' => 'Email',
                'email_provider' => $this->faker->randomElement(['Mailchimp', 'SendGrid', 'Campaign Monitor']),
            ]),
        ]);
    }

    public function socialMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'social_media',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'platform' => $this->faker->randomElement(['Facebook', 'LinkedIn', 'Twitter', 'Instagram']),
                'ad_format' => $this->faker->randomElement(['Image', 'Video', 'Carousel', 'Stories']),
            ]),
        ]);
    }

    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'event',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'platform' => 'Events',
                'event_type' => $this->faker->randomElement(['Conference', 'Trade Show', 'Networking', 'Workshop']),
                'venue' => $this->faker->optional()->company() . ' Center',
            ]),
        ]);
    }

    public function webinar(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'webinar',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'platform' => $this->faker->randomElement(['Zoom', 'WebEx', 'Google Meet', 'GoToWebinar']),
                'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            ]),
        ]);
    }

    /**
     * Budget States
     */
    public function withBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget' => $this->faker->randomFloat(2, 10000, 500000),
            'expected_revenue' => $this->faker->randomFloat(2, 20000, 1000000),
        ]);
    }

    public function withoutBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget' => null,
            'actual_cost' => null,
            'expected_revenue' => null,
            'actual_revenue' => null,
        ]);
    }

    /**
     * Other States
     */
    public function withResults(): static
    {
        $budget = $this->faker->randomFloat(2, 10000, 200000);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'budget' => $budget,
            'actual_cost' => $this->faker->randomFloat(2, $budget * 0.8, $budget * 1.1),
            'expected_revenue' => $budget * 3,
            'actual_revenue' => $this->faker->randomFloat(2, $budget * 2, $budget * 4),
        ]);
    }

    public function ownedBy(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
