<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Models\ARInvoice;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'authorized', 'captured', 'failed', 'refunded', 'cancelled']);
        $gateway = $this->faker->randomElement(['stripe', 'conekta', 'paypal']);
        $paymentMethod = $this->faker->randomElement(['card', 'bank_transfer', 'oxxo', 'spei']);

        return [
            'checkout_session_id' => CheckoutSession::factory(),
            'sales_order_id' => null,
            'ar_invoice_id' => null,
            'gateway' => $gateway,
            'payment_intent_id' => 'pi_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'transaction_id' => $this->faker->optional(0.7)->regexify('ch_[A-Za-z0-9]{24}'),
            'client_secret' => $this->faker->optional(0.8)->regexify('pi_[A-Za-z0-9]{24}_secret_[A-Za-z0-9]{24}'),
            'amount' => $this->faker->randomFloat(2, 100, 50000),
            'currency' => $this->faker->randomElement(['MXN', 'USD', 'EUR']),
            'status' => $status,
            'payment_method' => $paymentMethod,
            'card_brand' => $paymentMethod === 'card' ? $this->faker->randomElement(['visa', 'mastercard', 'amex']) : null,
            'card_last4' => $paymentMethod === 'card' ? $this->faker->numerify('####') : null,
            'gateway_response' => [
                'id' => $this->faker->regexify('[A-Za-z0-9]{24}'),
                'object' => 'payment_intent',
                'amount' => $this->faker->numberBetween(10000, 5000000),
                'currency' => 'mxn',
                'status' => $status,
            ],
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'authorized_at' => in_array($status, ['authorized', 'captured']) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'captured_at' => $status === 'captured' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'failed_at' => $status === 'failed' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'refunded_at' => $status === 'refunded' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'metadata' => [
                'customer_email' => $this->faker->optional(0.8)->email(),
                'customer_name' => $this->faker->optional(0.8)->name(),
            ],
        ];
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'authorized_at' => null,
            'captured_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the transaction is authorized.
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'authorized',
            'authorized_at' => now(),
            'captured_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the transaction is captured (successful).
     */
    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'captured',
            'authorized_at' => now()->subMinutes(5),
            'captured_at' => now(),
            'transaction_id' => 'ch_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'failed_at' => null,
            'refunded_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the transaction has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => 'Your card was declined.',
            'authorized_at' => null,
            'captured_at' => null,
            'refunded_at' => null,
            'transaction_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'authorized_at' => now()->subDays(5),
            'captured_at' => now()->subDays(5),
            'refunded_at' => now(),
            'transaction_id' => 'ch_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'failed_at' => null,
            'error_message' => null,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'refund_id' => 're_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'refund_reason' => 'requested_by_customer',
            ]),
        ]);
    }

    /**
     * Indicate that the transaction is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'authorized_at' => null,
            'captured_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the transaction uses Stripe gateway.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
        ]);
    }

    /**
     * Indicate that the transaction uses card payment method.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
            'card_brand' => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
            'card_last4' => $this->faker->numerify('####'),
        ]);
    }

    /**
     * Indicate that the transaction uses OXXO payment method.
     */
    public function oxxo(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'oxxo',
            'card_brand' => null,
            'card_last4' => null,
        ]);
    }
}
