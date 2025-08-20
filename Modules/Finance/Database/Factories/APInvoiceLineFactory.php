<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineFactory extends Factory
{
    protected $model = APInvoiceLine::class;

    public function definition(): array
    {
        return [
            'ap_invoice_id' => \Modules\Finance\Models\APInvoice::factory(),
            'description' => $this->faker->sentence(8),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 1, 1000),
            'discount' => $this->faker->randomFloat(2, 1, 1000),
            'line_total' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }

}
