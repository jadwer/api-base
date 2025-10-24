<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AuditLog;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 100),
            'model_type' => $this->faker->sentence(3),
            'model_id' => $this->faker->numberBetween(1, 100),
            'action' => $this->faker->sentence(3),
            'user_id' => 1, // TODO: Use existing User ID - \Modules\User\Models\User::inRandomOrder()->first()?->id ?? 1,
            'changes' => $this->faker->sentence(),
            'ip_address' => $this->faker->sentence(3),
            'user_agent' => $this->faker->optional(0.7)->paragraph(),
            'session_id' => $this->faker->optional(0.3)->regexify('sess_[a-f0-9]{32}'),
            'payload_hash' => $this->faker->sentence(3),
            'requires_retention' => $this->faker->boolean(70),
            'retention_until' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

}
