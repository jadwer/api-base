<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class BasicAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds for basic finance accounts
     */
    public function run(): void
    {
        $this->command->info('🏦 Seeding basic finance accounts...');

        // Create accounts if they don't exist (preserving existing data)

        $accounts = [
            [
                'id' => 1,
                'code' => '1100',
                'name' => 'Banco',
                'account_type' => Account::TYPE_ASSET,
                'is_postable' => true,
                'status' => 'active',
                'level' => 1,
                'parent_id' => null
            ],
            [
                'id' => 2, 
                'code' => '1200',
                'name' => 'Clientes',
                'account_type' => Account::TYPE_ASSET,
                'is_postable' => true,
                'status' => 'active',
                'level' => 1,
                'parent_id' => null
            ],
            [
                'id' => 3,
                'code' => '2100', 
                'name' => 'Proveedores',
                'account_type' => Account::TYPE_LIABILITY,
                'is_postable' => true,
                'status' => 'active',
                'level' => 1,
                'parent_id' => null
            ],
            [
                'id' => 4,
                'code' => '5000',
                'name' => 'Ingresos por Ventas',
                'account_type' => Account::TYPE_REVENUE,
                'is_postable' => true,
                'status' => 'active',
                'level' => 1,
                'parent_id' => null
            ],
            [
                'id' => 5,
                'code' => '4000',
                'name' => 'Gastos Generales',
                'account_type' => Account::TYPE_EXPENSE,
                'is_postable' => true,
                'status' => 'active',
                'level' => 1,
                'parent_id' => null
            ]
        ];

        foreach ($accounts as $account) {
            $existing = Account::find($account['id']);
            if (!$existing) {
                Account::create($account);
                $this->command->line("  ✅ Created: {$account['code']} - {$account['name']} ({$account['account_type']})");
            } else {
                $this->command->line("  ⏭️  Exists: {$existing->code} - {$existing->name} ({$existing->account_type})");
            }
        }

        $this->command->info('✅ Basic finance accounts seeded successfully!');
        $this->command->info('📋 Account Configuration:');
        $this->command->line('  • ID 1: Banco (Asset) - for payments/receipts');
        $this->command->line('  • ID 2: Clientes (Asset) - AR Control');
        $this->command->line('  • ID 3: Proveedores (Liability) - AP Control');
        $this->command->line('  • ID 4: Ingresos (Revenue) - Sales');
        $this->command->line('  • ID 5: Gastos (Expense) - Purchases');
    }
}