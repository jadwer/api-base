<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $levels = ['entry', 'junior', 'mid', 'senior', 'lead', 'manager', 'director', 'executive'];
        $level = $this->faker->randomElement($levels);

        // Salary ranges based on level
        $salaryRanges = [
            'entry' => [25000, 35000],
            'junior' => [35000, 50000],
            'mid' => [50000, 75000],
            'senior' => [75000, 100000],
            'lead' => [90000, 120000],
            'manager' => [100000, 150000],
            'director' => [120000, 180000],
            'executive' => [150000, 250000],
        ];

        [$minSalary, $maxSalary] = $salaryRanges[$level];

        return [
            'department_id' => Department::factory(),
            'title' => $this->faker->randomElement([
                'Desarrollador de Software',
                'Analista de Datos',
                'Gerente de Proyecto',
                'Diseñador UX/UI',
                'Ingeniero de DevOps',
                'Contador',
                'Ejecutivo de Ventas',
                'Especialista en Marketing',
                'Analista Financiero',
                'Coordinador de RRHH',
                'Asistente Administrativo',
                'Ingeniero de Calidad',
            ]),
            'description' => $this->faker->optional(0.8)->sentence(15),
            'level' => $level,
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the position is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the position is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Position with specific level.
     */
    public function level(string $level): static
    {
        $salaryRanges = [
            'entry' => [25000, 35000],
            'junior' => [35000, 50000],
            'mid' => [50000, 75000],
            'senior' => [75000, 100000],
            'lead' => [90000, 120000],
            'manager' => [100000, 150000],
            'director' => [120000, 180000],
            'executive' => [150000, 250000],
        ];

        [$minSalary, $maxSalary] = $salaryRanges[$level] ?? [25000, 35000];

        return $this->state(fn (array $attributes) => [
            'level' => $level,
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
        ]);
    }

    /**
     * Position for specific department.
     */
    public function forDepartment(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $departmentId,
        ]);
    }

    /**
     * Entry level position.
     */
    public function entry(): static
    {
        return $this->level('entry');
    }

    /**
     * Senior level position.
     */
    public function senior(): static
    {
        return $this->level('senior');
    }

    /**
     * Manager level position.
     */
    public function manager(): static
    {
        return $this->level('manager');
    }
}
