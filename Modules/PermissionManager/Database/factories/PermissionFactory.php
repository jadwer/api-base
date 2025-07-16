<?php

namespace Modules\PermissionManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PermissionManager\Models\Permission;


class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(),
            'guard_name' => 'api',
        ];
    }
}
