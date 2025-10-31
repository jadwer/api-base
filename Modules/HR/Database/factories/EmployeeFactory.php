<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;
use Modules\User\Models\User;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $hireDate = $this->faker->dateTimeBetween('-5 years', 'now');

        return [
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'user_id' => null, // Optional link to user account
            'employee_code' => 'EMP' . $this->faker->unique()->numerify('######'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional(0.8)->numerify('###-###-####'),
            'hire_date' => $hireDate,
            'birth_date' => $this->faker->optional(0.9)->dateTimeBetween('-60 years', '-22 years'),
            'salary' => $this->faker->randomFloat(2, 25000, 150000),
            'status' => 'active',
            'termination_date' => null,
            'termination_reason' => null,
            'address' => $this->faker->optional(0.7)->address(),
            'emergency_contact_name' => $this->faker->optional(0.8)->name(),
            'emergency_contact_phone' => $this->faker->optional(0.8)->numerify('###-###-####'),
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'termination_date' => null,
            'termination_reason' => null,
        ]);
    }

    /**
     * Indicate that the employee is on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_leave',
        ]);
    }

    /**
     * Indicate that the employee is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terminated',
            'termination_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'termination_reason' => $this->faker->randomElement([
                'Renuncia voluntaria',
                'Reestructuración organizacional',
                'Bajo rendimiento',
                'Violación de políticas',
                'Fin de contrato',
            ]),
        ]);
    }

    /**
     * Employee with linked user account.
     */
    public function withUser(int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Employee in specific department.
     */
    public function inDepartment(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $departmentId,
        ]);
    }

    /**
     * Employee with specific position.
     */
    public function withPosition(int $positionId): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => $positionId,
        ]);
    }

    /**
     * Employee with specific salary.
     */
    public function withSalary(float $salary): static
    {
        return $this->state(fn (array $attributes) => [
            'salary' => $salary,
        ]);
    }

    /**
     * Recent hire (hired in last 6 months).
     */
    public function recentHire(): static
    {
        return $this->state(fn (array $attributes) => [
            'hire_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Long-term employee (hired over 5 years ago).
     */
    public function longTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'hire_date' => $this->faker->dateTimeBetween('-15 years', '-5 years'),
        ]);
    }
}
