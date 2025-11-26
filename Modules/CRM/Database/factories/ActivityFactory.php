<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Activity;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['call', 'email', 'meeting', 'note', 'task']);

        return [
            'activity_type' => $type,
            'subject' => $this->getSubjectByType($type),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'activity_date' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'duration' => $this->getDurationByType($type),
            'outcome' => $this->faker->optional(0.5)->randomElement([
                'successful',
                'no answer',
                'callback requested',
                'not interested',
                'follow up scheduled',
                'closed deal',
            ]),
            'status' => 'pending',
            'user_id' => User::factory(),
            'lead_id' => null,
            'contact_id' => null,
            'campaign_id' => null,
            'metadata' => [
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'tags' => $this->faker->randomElements(['follow-up', 'demo', 'pricing', 'support', 'onboarding'], rand(0, 3)),
            ],
        ];
    }

    /**
     * Type States
     */
    public function call(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'call',
            'subject' => $this->getSubjectByType('call'),
            'duration' => $this->faker->numberBetween(5, 60),
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'email',
            'subject' => $this->getSubjectByType('email'),
            'duration' => null,
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'meeting',
            'subject' => $this->getSubjectByType('meeting'),
            'duration' => $this->faker->numberBetween(30, 240),
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'note',
            'subject' => $this->getSubjectByType('note'),
            'duration' => null,
        ]);
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'task',
            'subject' => $this->getSubjectByType('task'),
            'duration' => null,
        ]);
    }

    /**
     * Status States
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'activity_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'outcome' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'activity_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'outcome' => $this->faker->randomElement([
                'successful',
                'no answer',
                'callback requested',
                'follow up scheduled',
            ]),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'outcome' => 'cancelled by user',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'outcome' => null,
        ]);
    }

    /**
     * Relationship States
     */
    public function forLead(int|Lead $lead): static
    {
        $leadId = $lead instanceof Lead ? $lead->id : $lead;

        return $this->state(fn (array $attributes) => [
            'lead_id' => $leadId,
        ]);
    }

    public function forCampaign(int|Campaign $campaign): static
    {
        $campaignId = $campaign instanceof Campaign ? $campaign->id : $campaign;

        return $this->state(fn (array $attributes) => [
            'campaign_id' => $campaignId,
        ]);
    }

    public function assignedTo(int|User $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Helper methods
     */
    private function getSubjectByType(string $type): string
    {
        return match ($type) {
            'call' => $this->faker->randomElement([
                'Follow-up call with prospect',
                'Discovery call',
                'Demo scheduling call',
                'Price negotiation call',
                'Check-in call',
            ]),
            'email' => $this->faker->randomElement([
                'Product information request',
                'Quote follow-up',
                'Meeting confirmation',
                'Thank you email',
                'Newsletter sent',
            ]),
            'meeting' => $this->faker->randomElement([
                'Product demo meeting',
                'Contract negotiation meeting',
                'Kick-off meeting',
                'Review meeting',
                'Strategy session',
            ]),
            'note' => $this->faker->randomElement([
                'Lead qualification notes',
                'Client requirements',
                'Budget discussion',
                'Competitor analysis',
                'Next steps',
            ]),
            'task' => $this->faker->randomElement([
                'Send proposal',
                'Prepare quote',
                'Schedule demo',
                'Follow up on email',
                'Update CRM',
            ]),
        };
    }

    private function getDurationByType(string $type): ?int
    {
        return match ($type) {
            'call' => $this->faker->numberBetween(5, 60),
            'meeting' => $this->faker->numberBetween(30, 240),
            'email', 'note', 'task' => null,
        };
    }
}
