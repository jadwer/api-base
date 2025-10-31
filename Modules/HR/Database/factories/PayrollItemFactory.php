<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;

class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    public function definition(): array
    {
        $basicSalary = $this->faker->randomFloat(2, 3000, 10000);
        $overtimePay = $this->faker->randomFloat(2, 0, 1000);
        $bonuses = $this->faker->randomFloat(2, 0, 2000);
        $deductions = ($basicSalary + $overtimePay + $bonuses) * $this->faker->randomFloat(2, 0.10, 0.25);
        $netPay = ($basicSalary + $overtimePay + $bonuses) - $deductions;

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'basic_salary' => $basicSalary,
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'status' => $this->faker->randomElement(['draft', 'pending', 'paid']),
            'paid_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'paid_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function forEmployee(int $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }

    public function forPeriod(int $periodId): static
    {
        return $this->state(fn (array $attributes) => [
            'payroll_period_id' => $periodId,
        ]);
    }

    public function withOvertime(float $overtimePay = 500.00): static
    {
        return $this->state(fn (array $attributes) => [
            'overtime_pay' => $overtimePay,
        ]);
    }

    public function withBonus(float $bonus = 1000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'bonuses' => $bonus,
        ]);
    }

    public function noDeductions(): static
    {
        return $this->state(fn (array $attributes) => [
            'deductions' => 0,
        ]);
    }
}
