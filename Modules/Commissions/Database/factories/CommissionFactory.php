<?php

namespace Modules\Commissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Commissions\Models\Commission;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;

class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        $baseAmount = $this->faker->randomFloat(2, 100, 50000);
        $pct = $this->faker->randomFloat(2, 1, 15);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'ar_invoice_id' => null,
            'user_id' => User::factory(),
            'contact_id' => Contact::factory()->state(['is_customer' => true]),
            'base_amount' => $baseAmount,
            'commission_pct' => $pct,
            'commission_amount' => round($baseAmount * $pct / 100, 2),
            'status' => Commission::STATUS_PENDING,
            'earned_at' => null,
            'paid_at' => null,
            'payment_reference' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_PENDING,
            'earned_at' => null,
            'paid_at' => null,
        ]);
    }

    public function earned(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_EARNED,
            'earned_at' => now(),
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_PAID,
            'earned_at' => now()->subDay(),
            'paid_at' => now(),
            'payment_reference' => 'PAY-' . strtoupper($this->faker->bothify('####??')),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_CANCELLED,
        ]);
    }
}
