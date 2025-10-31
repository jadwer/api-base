<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductQuestion;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class ProductQuestionFactory extends Factory
{
    protected $model = ProductQuestion::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'question' => $this->faker->randomElement([
                '¿Este producto viene con garantía?',
                '¿Cuál es el tiempo de envío?',
                '¿Está disponible en otros colores?',
                '¿Es compatible con...?',
                '¿Incluye el manual de instrucciones?',
                '¿Cuáles son las dimensiones exactas?',
                '¿El precio incluye IVA?',
                '¿Tienen stock disponible?',
                '¿Puedo recogerlo en tienda?',
                '¿Aceptan devoluciones?',
            ]),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    /**
     * Indicate that the question is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the question is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the question is rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Add answers to the question
     */
    public function hasAnswers(int $count = 1): static
    {
        return $this->has(
            \Modules\Ecommerce\Models\ProductAnswer::factory()->count($count),
            'answers'
        );
    }
}
