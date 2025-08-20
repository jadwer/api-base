<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartFactory extends Factory
{
    protected $model = ShoppingCart::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 2000);
        $discountPercent = $this->faker->optional(0.4)->numberBetween(5, 30);
        $discountAmount = $discountPercent ? $subtotal * ($discountPercent / 100) : 0;
        $taxAmount = ($subtotal - $discountAmount) * 0.16; // 16% IVA México
        $shippingAmount = $this->faker->randomElement([0, 99, 199, 299]); // Envío gratuito o costos fijos

        return [
            'session_id' => $this->faker->optional(0.7)->regexify('sess_[a-f0-9]{32}'),
            'user_id' => \Modules\User\Models\User::inRandomOrder()->first()?->id ?? 1,
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive', 'expired']), // Más carritos activos
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'total_amount' => round($subtotal - $discountAmount + $taxAmount + $shippingAmount, 2),
            'currency' => 'MXN', // Principalmente pesos mexicanos
            'coupon_code' => $this->faker->optional(0.3)->randomElement(['SAVE10', 'WELCOME15', 'SUMMER20', 'NEWUSER', 'FREESHIP']),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'shipping_amount' => $shippingAmount,
            'notes' => $this->faker->optional(0.3)->randomElement([
                'Entrega urgente solicitada',
                'Dejar en portería si no hay nadie',
                'Llamar antes de entregar',
                'Cliente preferencial VIP',
                'Primera compra - bienvenida'
            ]),
            'metadata' => $this->faker->optional(0.6)->randomElements([
                'utm_source' => $this->faker->randomElement(['google', 'facebook', 'instagram', 'direct']),
                'device' => $this->faker->randomElement(['mobile', 'desktop', 'tablet']),
                'referrer' => $this->faker->randomElement(['homepage', 'product-page', 'search', 'promo-email']),
                'session_duration' => $this->faker->numberBetween(120, 3600), // segundos
                'pages_viewed' => $this->faker->numberBetween(1, 15)
            ], rand(2, 5))
        ];
    }

    /**
     * Active ShoppingCart
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ShoppingCart
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
