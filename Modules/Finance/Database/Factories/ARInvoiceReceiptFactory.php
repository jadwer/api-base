<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\ARInvoiceReceipt;

class ARInvoiceReceiptFactory extends Factory
{
    protected $model = ARInvoiceReceipt::class;

    public function definition(): array
    {
        return [
            'ar_invoice_id' => $this->faker->numberBetween(1, 100),
            'ar_receipt_id' => $this->faker->numberBetween(1, 100),
            'amount_applied' => $this->faker->randomFloat(2, 1, 1000),
            'applied_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'exchange_rate_at_apply' => $this->faker->randomFloat(2, 0, 100),
        ];
    }

}
