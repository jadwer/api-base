<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\User\Models\User;

class CheckoutSessionFactory extends Factory
{
    protected $model = CheckoutSession::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 500);
        $shipping = $this->faker->randomFloat(2, 5, 50);
        $tax = round($subtotal * 0.16, 2); // 16% IVA
        $discount = 0;
        $total = $subtotal + $shipping + $tax - $discount;

        return [
            'shopping_cart_id' => ShoppingCart::factory(),
            'user_id' => User::factory(),
            'status' => 'initiated',
            'step' => 'address',
            'shipping_method_id' => null,
            'billing_address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => 'México',
            ],
            'shipping_address' => null,
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'payment_method' => null,
            'payment_intent_id' => null,
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'currency' => 'MXN',
            'expires_at' => now()->addHours(2),
            'completed_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'referrer' => $this->faker->optional()->url(),
            ],
        ];
    }

    /**
     * Indicate that the checkout session has shipping set.
     */
    public function withShipping(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'step' => 'shipping',
                'shipping_address' => [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => 'México',
                ],
                'shipping_method_id' => ShippingMethod::factory(),
            ];
        });
    }

    /**
     * Indicate that the checkout session is ready for payment.
     */
    public function readyForPayment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'step' => 'payment',
                'status' => 'initiated',
                'shipping_address' => [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => 'México',
                ],
                'shipping_method_id' => ShippingMethod::factory(),
            ];
        });
    }

    /**
     * Indicate that the checkout session has pending payment.
     */
    public function paymentPending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'step' => 'payment',
                'status' => 'payment_pending',
                'payment_method' => $this->faker->randomElement(['stripe', 'paypal', 'mercadopago']),
                'payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'shipping_address' => [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => 'México',
                ],
                'shipping_method_id' => ShippingMethod::factory(),
            ];
        });
    }

    /**
     * Indicate that the checkout session has confirmed payment.
     */
    public function paymentConfirmed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'step' => 'confirmation',
                'status' => 'payment_confirmed',
                'payment_method' => $this->faker->randomElement(['stripe', 'paypal', 'mercadopago']),
                'payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'shipping_address' => [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => 'México',
                ],
                'shipping_method_id' => ShippingMethod::factory(),
            ];
        });
    }

    /**
     * Indicate that the checkout session is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $completedAt = $this->faker->dateTimeBetween('-1 month', 'now');

            return [
                'step' => 'confirmation',
                'status' => 'completed',
                'payment_method' => $this->faker->randomElement(['stripe', 'paypal', 'mercadopago']),
                'payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'shipping_address' => [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => 'México',
                ],
                'shipping_method_id' => ShippingMethod::factory(),
                'completed_at' => $completedAt,
                'expires_at' => now()->addHours(2),
            ];
        });
    }

    /**
     * Indicate that the checkout session is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expired',
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            ];
        });
    }

    /**
     * Indicate that the checkout session is abandoned.
     */
    public function abandoned(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'abandoned',
                'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            ];
        });
    }

    /**
     * Indicate that the checkout session is for a guest user.
     */
    public function guest(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }
}
