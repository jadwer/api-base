<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Coupon;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['percentage', 'fixed_amount', 'free_shipping']);
        $maxUses = $this->faker->optional(0.7)->numberBetween(10, 1000); // Algunos ilimitados
        $usedCount = $maxUses ? $this->faker->numberBetween(0, min($maxUses, 50)) : $this->faker->numberBetween(0, 100);

        return [
            'code' => strtoupper($this->faker->unique()->lexify('????##')),
            'name' => $this->faker->randomElement([
                'Descuento de Bienvenida',
                'Promoción de Verano',
                'Oferta Flash',
                'Descuento Estudiante',
                'Black Friday Especial',
                'Envío Gratuito',
                'Cliente Fiel',
                'Fin de Semana'
            ]),
            'description' => $this->faker->optional(0.8)->randomElement([
                'Descuento exclusivo para nuevos clientes',
                'Promoción por tiempo limitado',
                'Oferta especial de temporada',
                'Descuento para estudiantes universitarios',
                'Envío gratuito en compras superiores a $500',
                'Recompensa para clientes frecuentes',
                'Oferta flash de 24 horas',
                'Descuento de fin de semana'
            ]),
            'type' => $type,
            'value' => match ($type) {
                'percentage' => $this->faker->randomElement([10, 15, 20, 25, 30, 50]),
                'fixed_amount' => $this->faker->randomElement([50, 100, 150, 200, 300, 500]),
                'free_shipping' => 0,
            },
            'min_amount' => $this->faker->optional(0.6)->randomElement([100, 200, 500, 1000, 1500]),
            'max_amount' => $this->faker->optional(0.3)->randomElement([2000, 5000, 10000]),
            'max_uses' => $maxUses,
            'used_count' => $usedCount,
            'starts_at' => $this->faker->optional(0.8)->dateTimeBetween('-7 days', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('+3 days', '+60 days'),
            'is_active' => $this->faker->boolean(85), // Más cupones activos
            'customer_ids' => $this->faker->optional(0.3)->randomElements([1, 2, 3, 4, 5, 6, 7, 8], rand(1, 3)),
            'product_ids' => $this->faker->optional(0.4)->randomElements([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], rand(1, 5)),
            'category_ids' => $this->faker->optional(0.5)->randomElements([1, 2, 3, 4, 5], rand(1, 2))
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
