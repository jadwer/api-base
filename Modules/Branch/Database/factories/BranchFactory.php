<?php

namespace Modules\Branch\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Branch\Models\Branch;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => strtoupper($this->faker->unique()->bothify('???-###')),
            'address' => $this->faker->boolean(70) ? $this->faker->paragraph() : null,
            'city' => $this->faker->boolean(70) ? $this->faker->city() : null,
            'state' => $this->faker->boolean(70) ? $this->faker->state() : null,
            'postal_code' => $this->faker->boolean(70) ? $this->faker->numerify('#####') : null,
            'phone' => $this->faker->boolean(70) ? $this->faker->numerify('55 #### ####') : null,
            'email' => $this->faker->boolean(70) ? $this->faker->unique()->safeEmail() : null,
            'is_active' => true,
            'is_main' => false,
        ];
    }
}
