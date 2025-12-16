<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\PipelineStage;
use Modules\User\Models\User;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10000, 500000);
        $probability = $this->faker->numberBetween(20, 90);

        return [
            'name' => $this->faker->catchPhrase() . ' Deal',
            'description' => $this->faker->optional()->paragraph(),
            'amount' => $amount,
            'probability' => $probability,
            'expected_revenue' => null, // Auto-calculated by model
            'actual_revenue' => null,
            'close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'status' => 'open',
            'stage' => $this->faker->randomElement(['qualification', 'needs analysis', 'proposal', 'negotiation']),
            'forecast_category' => $this->faker->randomElement(['pipeline', 'best_case', 'commit']),
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'pipeline_stage_id' => null, // Will be set when PipelineStage is available
            'contact_id' => null, // Will be populated when Contact module is implemented
            'source' => $this->faker->randomElement(['website', 'referral', 'cold call', 'trade show', 'social media']),
            'next_step' => $this->faker->optional()->sentence(),
            'loss_reason' => null,
            'won_at' => null,
            'lost_at' => null,
            'metadata' => [
                'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail']),
                'decision_maker' => $this->faker->name(),
                'competitors' => $this->faker->randomElement(['CompetitorA', 'CompetitorB', 'CompetitorC', 'None']),
            ],
        ];
    }

    /**
     * Status States
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'won_at' => null,
            'lost_at' => null,
            'actual_revenue' => null,
            'loss_reason' => null,
        ]);
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'probability' => 100,
            'forecast_category' => 'closed',
            'won_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'actual_revenue' => $attributes['amount'] ?? $this->faker->randomFloat(2, 10000, 500000),
            'lost_at' => null,
            'loss_reason' => null,
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'probability' => 0,
            'lost_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'loss_reason' => $this->faker->randomElement([
                'Price too high',
                'Went with competitor',
                'Budget constraints',
                'Timeline not aligned',
                'Product not a fit',
                'Lost contact',
            ]),
            'won_at' => null,
            'actual_revenue' => null,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'abandoned',
            'probability' => 0,
            'lost_at' => $this->faker->dateTimeBetween('-60 days', '-30 days'),
            'loss_reason' => $this->faker->randomElement([
                'No response from lead',
                'Project cancelled',
                'Requirements changed',
                'Out of scope',
            ]),
            'won_at' => null,
            'actual_revenue' => null,
        ]);
    }

    /**
     * Value States
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 200000, 1000000),
            'probability' => $this->faker->numberBetween(60, 90),
        ]);
    }

    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 5000, 25000),
            'probability' => $this->faker->numberBetween(30, 60),
        ]);
    }

    /**
     * Probability States
     */
    public function highProbability(): static
    {
        return $this->state(fn (array $attributes) => [
            'probability' => $this->faker->numberBetween(75, 95),
            'forecast_category' => 'commit',
            'stage' => $this->faker->randomElement(['negotiation', 'proposal']),
        ]);
    }

    public function lowProbability(): static
    {
        return $this->state(fn (array $attributes) => [
            'probability' => $this->faker->numberBetween(10, 30),
            'forecast_category' => 'pipeline',
            'stage' => $this->faker->randomElement(['qualification', 'needs analysis']),
        ]);
    }

    /**
     * Forecast Category States
     */
    public function pipeline(): static
    {
        return $this->state(fn (array $attributes) => [
            'forecast_category' => 'pipeline',
            'probability' => $this->faker->numberBetween(20, 50),
        ]);
    }

    public function bestCase(): static
    {
        return $this->state(fn (array $attributes) => [
            'forecast_category' => 'best_case',
            'probability' => $this->faker->numberBetween(50, 70),
        ]);
    }

    public function commit(): static
    {
        return $this->state(fn (array $attributes) => [
            'forecast_category' => 'commit',
            'probability' => $this->faker->numberBetween(70, 95),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'forecast_category' => 'closed',
            'probability' => 100,
            'status' => 'won',
            'won_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Stage States
     */
    public function qualification(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'qualification',
            'probability' => $this->faker->numberBetween(20, 40),
        ]);
    }

    public function needsAnalysis(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'needs analysis',
            'probability' => $this->faker->numberBetween(40, 60),
        ]);
    }

    public function proposal(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'proposal',
            'probability' => $this->faker->numberBetween(60, 80),
        ]);
    }

    public function negotiation(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'negotiation',
            'probability' => $this->faker->numberBetween(75, 90),
        ]);
    }

    /**
     * Date States
     */
    public function closingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'close_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'probability' => $this->faker->numberBetween(70, 95),
        ]);
    }

    public function closingLater(): static
    {
        return $this->state(fn (array $attributes) => [
            'close_date' => $this->faker->dateTimeBetween('+6 months', '+12 months'),
            'probability' => $this->faker->numberBetween(20, 50),
        ]);
    }

    /**
     * Assignment States
     */
    public function assignedTo(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function fromLead(int $leadId): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_id' => $leadId,
        ]);
    }

    public function withPipelineStage(int $stageId): static
    {
        return $this->state(fn (array $attributes) => [
            'pipeline_stage_id' => $stageId,
        ]);
    }
}
