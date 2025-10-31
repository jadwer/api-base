<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\PayrollPeriod;
use Carbon\Carbon;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $periodType = $this->faker->randomElement(['weekly', 'biweekly', 'monthly']);
        $startDate = Carbon::now()->startOfMonth()->subMonths(rand(0, 3));

        // Calculate end date based on period type
        $endDate = match($periodType) {
            'weekly' => $startDate->copy()->addWeek()->subDay(),
            'biweekly' => $startDate->copy()->addWeeks(2)->subDay(),
            'monthly' => $startDate->copy()->endOfMonth(),
        };

        $paymentDate = $endDate->copy()->addDays(5);

        $totalGross = $this->faker->randomFloat(2, 50000, 200000);
        $totalDeductions = $totalGross * $this->faker->randomFloat(2, 0.15, 0.30);
        $totalNet = $totalGross - $totalDeductions;

        return [
            'name' => $startDate->format('F Y') . ' Payroll',
            'period_type' => $periodType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
            'status' => $this->faker->randomElement(['draft', 'processing', 'paid', 'closed']),
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net' => $totalNet,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Paid status.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    /**
     * Closed status.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Monthly period.
     */
    public function monthly(): static
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $paymentDate = $endDate->copy()->addDays(5);

        return $this->state(fn (array $attributes) => [
            'name' => $startDate->format('F Y') . ' Payroll',
            'period_type' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }

    /**
     * Biweekly period.
     */
    public function biweekly(): static
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = $startDate->copy()->addWeeks(2)->subDay();
        $paymentDate = $endDate->copy()->addDays(3);

        return $this->state(fn (array $attributes) => [
            'name' => 'Biweekly ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'period_type' => 'biweekly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }

    /**
     * Weekly period.
     */
    public function weekly(): static
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        $paymentDate = $endDate->copy()->addDays(2);

        return $this->state(fn (array $attributes) => [
            'name' => 'Weekly ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'period_type' => 'weekly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }

    /**
     * Current month period.
     */
    public function currentMonth(): static
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $paymentDate = $endDate->copy()->addDays(5);

        return $this->state(fn (array $attributes) => [
            'name' => $startDate->format('F Y') . ' Payroll',
            'period_type' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }

    /**
     * Last month period.
     */
    public function lastMonth(): static
    {
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $paymentDate = $endDate->copy()->addDays(5);

        return $this->state(fn (array $attributes) => [
            'name' => $startDate->format('F Y') . ' Payroll',
            'period_type' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }

    /**
     * Period for a specific month.
     */
    public function forMonth(int $month, int $year = null): static
    {
        $year = $year ?? Carbon::now()->year;
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $paymentDate = $endDate->copy()->addDays(5);

        return $this->state(fn (array $attributes) => [
            'name' => $startDate->format('F Y') . ' Payroll',
            'period_type' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ]);
    }
}
