<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class BasicAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏦 Creating basic accounts for Finance Fase 1...');

        // Basic Chart of Accounts for Fase 1
        $basicAccounts = [
            [
                'id' => 1,
                'code' => '1100',
                'name' => 'Banco',
                'account_type' => 'asset',
                'level' => 1,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
            [
                'id' => 2,
                'code' => '1200',
                'name' => 'Clientes',
                'account_type' => 'asset',
                'level' => 1,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
            [
                'id' => 3,
                'code' => '2100',
                'name' => 'Proveedores',
                'account_type' => 'liability',
                'level' => 1,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
            [
                'id' => 4,
                'code' => '5000',
                'name' => 'Ingresos',
                'account_type' => 'revenue',
                'level' => 1,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
            [
                'id' => 5,
                'code' => '4000',
                'name' => 'Gastos',
                'account_type' => 'expense',
                'level' => 1,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
        ];

        foreach ($basicAccounts as $accountData) {
            Account::updateOrCreate(
                ['id' => $accountData['id']],
                $accountData
            );
            
            $this->command->info("✅ Created account: {$accountData['code']} - {$accountData['name']}");
        }

        // Additional supporting accounts for complete F1 functionality
        $supportingAccounts = [
            [
                'code' => '1000',
                'name' => 'Activos',
                'account_type' => 'asset',
                'level' => 0,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => false, // Header account
                'status' => 'active',
            ],
            [
                'code' => '2000',
                'name' => 'Pasivos',
                'account_type' => 'liability',
                'level' => 0,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => false, // Header account
                'status' => 'active',
            ],
            [
                'code' => '3000',
                'name' => 'Capital',
                'account_type' => 'equity',
                'level' => 0,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => false, // Header account
                'status' => 'active',
            ],
            [
                'code' => '4001',
                'name' => 'Gastos de Oficina',
                'account_type' => 'expense',
                'level' => 2,
                'parent_id' => 5, // Under Gastos
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
            [
                'code' => '5001',
                'name' => 'Ventas de Servicios',
                'account_type' => 'revenue',
                'level' => 2,
                'parent_id' => 4, // Under Ingresos
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ],
        ];

        foreach ($supportingAccounts as $accountData) {
            Account::updateOrCreate(
                ['code' => $accountData['code']],
                $accountData
            );
            
            $this->command->info("✅ Created supporting account: {$accountData['code']} - {$accountData['name']}");
        }

        $this->command->info('🎯 Basic chart of accounts created for Fase 1!');
    }
}