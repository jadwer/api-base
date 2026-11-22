<?php

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\User\Models\User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // Sufijo aleatorio: unique() de faker no conoce los correos sembrados y en
            // paralelo llego a repetir uno (UNIQUE users.email, 22-11).
            'email' => fake()->unique()->userName() . '.' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)) . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'status' => 'active',
        ];
    }

    public function withRole(string $role): self
    {
        return $this->afterCreating(function ($user) use ($role) {
            $user->assignRole($role);
        });
    }
}
