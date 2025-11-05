<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\PipelineStage;

class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Prospecting',
                'Qualification',
                'Proposal',
                'Negotiation',
                'Closed Won',
                'Closed Lost',
                'New',
                'Contacted',
                'Qualified',
            ]),
            'type' => $this->faker->randomElement(['lead', 'opportunity']),
            'probability' => $this->faker->numberBetween(0, 100),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ];
    }

    /**
     * Prospecting stage for opportunities.
     */
    public function prospecting(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Prospecting',
            'type' => 'opportunity',
            'probability' => 10,
            'sort_order' => 1,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Qualification stage for opportunities.
     */
    public function qualification(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Qualification',
            'type' => 'opportunity',
            'probability' => 25,
            'sort_order' => 2,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Proposal stage for opportunities.
     */
    public function proposal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Proposal',
            'type' => 'opportunity',
            'probability' => 50,
            'sort_order' => 3,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Negotiation stage for opportunities.
     */
    public function negotiation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Negotiation',
            'type' => 'opportunity',
            'probability' => 75,
            'sort_order' => 4,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Closed Won stage.
     */
    public function closedWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Closed Won',
            'type' => 'opportunity',
            'probability' => 100,
            'sort_order' => 5,
            'is_active' => true,
            'is_closed_won' => true,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Closed Lost stage.
     */
    public function closedLost(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Closed Lost',
            'type' => 'opportunity',
            'probability' => 0,
            'sort_order' => 6,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => true,
        ]);
    }

    /**
     * New lead stage.
     */
    public function newLead(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'New',
            'type' => 'lead',
            'probability' => 10,
            'sort_order' => 1,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Contacted lead stage.
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Contacted',
            'type' => 'lead',
            'probability' => 30,
            'sort_order' => 2,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * Qualified lead stage.
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Qualified',
            'type' => 'lead',
            'probability' => 50,
            'sort_order' => 3,
            'is_active' => true,
            'is_closed_won' => false,
            'is_closed_lost' => false,
        ]);
    }

    /**
     * For lead type.
     */
    public function forLeads(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lead',
        ]);
    }

    /**
     * For opportunity type.
     */
    public function forOpportunities(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'opportunity',
        ]);
    }

    /**
     * Active stage.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive stage.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
