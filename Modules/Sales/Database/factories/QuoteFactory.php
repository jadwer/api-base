<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\Quote;
use Modules\Contacts\Models\Contact;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'quote_number' => 'QT-' . now()->format('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'quote_date' => now(),
            'valid_until' => now()->addDays(30),
            'estimated_eta' => $this->faker->randomElement(['1-2 semanas', '3-5 días hábiles', '2-3 semanas']),
            'subtotal_amount' => $this->faker->randomFloat(2, 100, 10000),
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'currency' => 'MXN',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'sent_at' => now()->subDays(2),
            'accepted_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'sent_at' => now()->subDays(2),
            'rejected_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_until' => now()->subDays(1),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'sent_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(3),
            'converted_at' => now(),
        ]);
    }
}
