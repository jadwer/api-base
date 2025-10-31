<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductAnswer;
use Modules\Ecommerce\Models\ProductQuestion;
use Modules\User\Models\User;

class ProductAnswerFactory extends Factory
{
    protected $model = ProductAnswer::class;

    public function definition(): array
    {
        return [
            'question_id' => ProductQuestion::factory(),
            'user_id' => User::factory(),
            'answer' => $this->faker->randomElement([
                'Sí, incluye garantía del fabricante por 1 año.',
                'El tiempo de envío es de 3 a 5 días hábiles.',
                'Actualmente solo está disponible en el color mostrado.',
                'Sí, es compatible con la mayoría de los modelos.',
                'Incluye manual de instrucciones en español.',
                'Las dimensiones son 30cm x 20cm x 10cm.',
                'Sí, el precio incluye IVA.',
                'Sí, tenemos stock disponible.',
                'Puedes recogerlo en nuestra tienda física.',
                'Aceptamos devoluciones dentro de los primeros 30 días.',
            ]),
            'is_verified' => $this->faker->boolean(30), // 30% verified
        ];
    }

    /**
     * Indicate that the answer is verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the answer is not verified
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }
}
