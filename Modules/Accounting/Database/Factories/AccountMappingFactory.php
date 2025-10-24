<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingFactory extends Factory
{
    protected $model = AccountMapping::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 100),
            'mapping_type' => $this->faker->sentence(3),
            'account_id' => $this->faker->numberBetween(1, 100),
            'version' => $this->faker->numberBetween(1, 100),
            'effective_from' => $this->faker->sentence(),
            'effective_to' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(70),
            'created_by_id' => $this->faker->numberBetween(1, 100),
            'notes' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

}
