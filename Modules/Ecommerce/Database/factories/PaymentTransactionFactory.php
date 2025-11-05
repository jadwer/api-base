<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Models\ARInvoice;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $gateway = $this->faker->randomElement(['stripe', 'paypal', 'mercadopago']);
        $method = $this->faker->randomElement(['credit_card', 'debit_card', 'bank_transfer', 'paypal', 'oxxo']);

        return [
            'checkout_session_id' => CheckoutSession::factory(),
            'sales_order_id' => null,
            'ar_invoice_id' => null,
            'transaction_id' => $this->generateTransactionId($gateway),
            'gateway' => $gateway,
            'payment_method' => $method,
            'status' => 'pending',
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'currency' => 'MXN',
            'gateway_response' => [
                'gateway_transaction_id' => $this->faker->uuid(),
                'response_code' => '00',
                'response_message' => 'Success',
                'timestamp' => now()->toIso8601String(),
            ],
            'error_message' => null,
            'processed_at' => null,
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'attempt_number' => 1,
            ],
        ];
    }

    /**
     * Generate a realistic transaction ID based on the gateway.
     */
    private function generateTransactionId(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'ch_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'paypal' => $this->faker->regexify('[A-Z0-9]{17}'),
            'mercadopago' => $this->faker->numerify('##########'),
            default => $this->faker->uuid(),
        };
    }

    /**
     * Indicate that the transaction is authorized.
     */
    public function authorized(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'authorized',
                'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'gateway_response' => [
                    'gateway_transaction_id' => $this->faker->uuid(),
                    'response_code' => '00',
                    'response_message' => 'Authorized',
                    'authorization_code' => $this->faker->regexify('[A-Z0-9]{6}'),
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Indicate that the transaction is captured (completed).
     */
    public function captured(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'captured',
                'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'gateway_response' => [
                    'gateway_transaction_id' => $this->faker->uuid(),
                    'response_code' => '00',
                    'response_message' => 'Captured',
                    'authorization_code' => $this->faker->regexify('[A-Z0-9]{6}'),
                    'capture_id' => $this->faker->uuid(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $errors = [
                'Insufficient funds',
                'Card declined',
                'Invalid card number',
                'Expired card',
                'Incorrect CVC',
                'Transaction timeout',
                'Gateway error',
            ];

            return [
                'status' => 'failed',
                'error_message' => $this->faker->randomElement($errors),
                'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'gateway_response' => [
                    'gateway_transaction_id' => $this->faker->uuid(),
                    'response_code' => $this->faker->randomElement(['05', '51', '14', '54', '57', '91']),
                    'response_message' => 'Declined',
                    'timestamp' => now()->toIso8601String(),
                ],
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'attempt_number' => $this->faker->numberBetween(1, 3),
                ]),
            ];
        });
    }

    /**
     * Indicate that the transaction is refunded.
     */
    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'refunded',
                'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'gateway_response' => [
                    'gateway_transaction_id' => $this->faker->uuid(),
                    'response_code' => '00',
                    'response_message' => 'Refunded',
                    'refund_id' => $this->faker->uuid(),
                    'refund_date' => now()->toIso8601String(),
                    'timestamp' => now()->toIso8601String(),
                ],
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'refund_reason' => $this->faker->randomElement([
                        'Customer request',
                        'Duplicate charge',
                        'Product not available',
                        'Order cancelled',
                    ]),
                ]),
            ];
        });
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'processed_at' => null,
                'gateway_response' => [
                    'gateway_transaction_id' => $this->faker->uuid(),
                    'response_code' => 'pending',
                    'response_message' => 'Payment pending',
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Indicate that the transaction uses Stripe.
     */
    public function stripe(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'gateway' => 'stripe',
                'transaction_id' => 'ch_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card']),
            ];
        });
    }

    /**
     * Indicate that the transaction uses PayPal.
     */
    public function paypal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'gateway' => 'paypal',
                'transaction_id' => $this->faker->regexify('[A-Z0-9]{17}'),
                'payment_method' => 'paypal',
            ];
        });
    }

    /**
     * Indicate that the transaction uses MercadoPago.
     */
    public function mercadopago(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'gateway' => 'mercadopago',
                'transaction_id' => $this->faker->numerify('##########'),
                'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'oxxo']),
            ];
        });
    }

    /**
     * Indicate that the transaction is linked to a sales order.
     */
    public function withSalesOrder(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'sales_order_id' => SalesOrder::factory(),
            ];
        });
    }

    /**
     * Indicate that the transaction is linked to an AR invoice.
     */
    public function withARInvoice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'ar_invoice_id' => ARInvoice::factory(),
            ];
        });
    }
}
