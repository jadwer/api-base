<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\FiscalPeriod;
use App\Models\User;

class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    private static $currentYear = 2020;
    private static $currentMonth = 1;

    public function definition(): array
    {
        // Use sequential periods to avoid duplicates
        $year = self::$currentYear;
        $month = self::$currentMonth;

        // Increment for next call
        self::$currentMonth++;
        if (self::$currentMonth > 12) {
            self::$currentMonth = 1;
            self::$currentYear++;
        }

        $startDate = \Carbon\Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'name' => sprintf('%04d-%02d-%s', $year, $month, substr(uniqid(), -6)),
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'open', // Default to open - use closed() state for closed periods
            'closed_at' => null,
            'closed_by_id' => null,
            'closing_entry_id' => null,
            'metadata' => [],
        ];
    }

    /**
     * Open FiscalPeriod
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'closed_at' => null,
            'closed_by_id' => null,
        ]);
    }

    /**
     * Closed FiscalPeriod - creates a user for closed_by_id
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $user = User::first() ?? User::factory()->create();
            return [
                'status' => 'closed',
                'closed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'closed_by_id' => $user->id,
            ];
        });
    }

    /**
     * Locked FiscalPeriod
     */
    public function locked(): static
    {
        return $this->state(function (array $attributes) {
            $user = User::first() ?? User::factory()->create();
            return [
                'status' => 'locked',
                'closed_at' => $this->faker->dateTimeBetween('-60 days', '-30 days'),
                'closed_by_id' => $user->id,
            ];
        });
    }

    /**
     * Create FiscalPeriod for a specific date
     */
    public function forDate(\Carbon\Carbon $date): static
    {
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'name' => sprintf('%04d-%02d', $date->year, $date->month),
            'year' => $date->year,
            'month' => $date->month,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'open',
        ]);
    }

    /**
     * Create FiscalPeriod for current month
     */
    public function current(): static
    {
        return $this->forDate(\Carbon\Carbon::now());
    }
}
