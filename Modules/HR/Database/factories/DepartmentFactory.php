<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Recursos Humanos',
                'Finanzas',
                'Tecnología',
                'Ventas',
                'Marketing',
                'Operaciones',
                'Soporte al Cliente',
                'Investigación y Desarrollo',
                'Compras',
                'Administración',
                'Legal',
                'Logística',
            ]),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'manager_id' => null, // Will be set when Employee model exists
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the department is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the department is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Department with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Department with manager (requires Employee to exist).
     */
    public function withManager(int $managerId): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => $managerId,
        ]);
    }
}
