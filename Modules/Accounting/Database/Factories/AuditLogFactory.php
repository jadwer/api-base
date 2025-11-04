<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AuditLog;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $models = [
            'Modules\Accounting\Models\Account',
            'Modules\Accounting\Models\JournalEntry',
            'Modules\Accounting\Models\FiscalPeriod',
            'Modules\Accounting\Models\ExchangeRate'
        ];

        $actions = ['created', 'updated', 'deleted', 'approved', 'posted', 'reversed'];
        $action = $this->faker->randomElement($actions);

        $changes = [
            'old' => ['status' => 'draft', 'total_debit' => 1000.00],
            'new' => ['status' => 'approved', 'total_debit' => 1000.00]
        ];

        return [
            'company_id' => null,
            'model_type' => $this->faker->randomElement($models),
            'model_id' => $this->faker->numberBetween(1, 100),
            'action' => $action,
            'user_id' => \Modules\User\Models\User::factory(),
            'changes' => $changes,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->optional(0.8)->userAgent(),
            'session_id' => $this->faker->optional(0.7)->regexify('sess_[a-f0-9]{32}'),
            'payload_hash' => $this->faker->sha256(),
            'requires_retention' => $this->faker->boolean(30),
            'retention_until' => $this->faker->optional(0.3)->dateTimeBetween('+1 year', '+7 years'),
            'metadata' => [],
        ];
    }

}
