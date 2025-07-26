<?php

namespace Modules\Product\Database\Factories;

use Modules\Product\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        static $counter = 1;
        
        return [
            'name' => $this->faker->words(2, true) . ' Unit',
            'code' => 'test' . $counter++,
            'unit_type' => $this->faker->randomElement([
                'weight', 'volume', 'length', 'quantity'
            ]),
        ];
    }
}
