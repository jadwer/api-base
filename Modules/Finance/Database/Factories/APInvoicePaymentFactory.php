<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\APInvoicePayment;

class APInvoicePaymentFactory extends Factory
{
    protected $model = APInvoicePayment::class;

    public function definition(): array
    {
        return [
            'ap_invoice_id' => $this->faker->numberBetween(1, 100),
            'ap_payment_id' => $this->faker->numberBetween(1, 100),
            'amount_applied' => $this->faker->randomFloat(2, 1, 1000),
            'applied_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'exchange_rate_at_apply' => $this->faker->randomFloat(2, 0, 100),
        ];
    }

}
