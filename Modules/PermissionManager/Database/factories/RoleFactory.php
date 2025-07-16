<?php

namespace Modules\PermissionManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PermissionManager\Models\Role;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(3),
            'guard_name' => 'api',
        ];
    }

    /**
     * Define state for 'god' role.
     */
    public function god(): static
    {
        return $this->state([
            'name' => 'god',
            'description' => 'Superadmin del sistema',
        ]);
    }

    /**
     * Define state for 'admin' role.
     */
    public function admin(): static
    {
        return $this->state([
            'name' => 'admin',
            'description' => 'Administrador general',
        ]);
    }

    /**
     * Define state for 'tech' role.
     */
    public function tech(): static
    {
        return $this->state([
            'name' => 'tech',
            'description' => 'Usuario técnico de soporte',
        ]);
    }

    /**
     * Define state for 'customer' role.
     */
    public function customer(): static
    {
        return $this->state([
            'name' => 'customer',
            'description' => 'Cliente registrado',
        ]);
    }

    /**
     * Define state for 'guest' role.
     */
    public function guest(): static
    {
        return $this->state([
            'name' => 'guest',
            'description' => 'Invitado sin login',
        ]);
    }
}
