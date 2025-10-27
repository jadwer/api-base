<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Illuminate\Support\Facades\Log;

/**
 * GLAccountsSeeder
 *
 * Crea las cuentas GL requeridas por el módulo Finance para funcionar correctamente.
 *
 * Cuentas requeridas:
 * - 1020: Banco (Asset) - Para registrar pagos recibidos
 * - 1100: Clientes / Accounts Receivable (Asset) - Para AR Invoices
 * - 2100: Proveedores / Accounts Payable (Liability) - Para AP Invoices
 * - 4100: Ingresos por Ventas (Revenue) - Para AR Invoices
 * - 5100: Gastos Operativos (Expense) - Para AP Invoices
 */
class GLAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // First, create required journals (AR, AP)
        $arJournal = Journal::firstOrCreate(
            ['code' => 'AR'],
            [
                'name' => 'Accounts Receivable',
                'description' => 'Journal for customer invoices and payments',
                'prefix' => 'AR',
                'type' => 'sales',
                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        $apJournal = Journal::firstOrCreate(
            ['code' => 'AP'],
            [
                'name' => 'Accounts Payable',
                'description' => 'Journal for supplier invoices and payments',
                'prefix' => 'AP',
                'type' => 'purchases',
                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        // 1. Banco (1020)
        $bankAccount = Account::firstOrCreate(
            ['code' => '1020'],
            [
                'name' => 'Banco',

                'account_type' => 'asset',
                'nature' => 'debit',
                'level' => 2,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => true,

                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        // 2. Clientes / Accounts Receivable (1100)
        $customerAccount = Account::firstOrCreate(
            ['code' => '1100'],
            [
                'name' => 'Clientes (Cuentas por Cobrar)',

                'account_type' => 'asset',
                'nature' => 'debit',
                'level' => 2,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => false,

                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        // 3. Proveedores / Accounts Payable (2100)
        $supplierAccount = Account::firstOrCreate(
            ['code' => '2100'],
            [
                'name' => 'Proveedores (Cuentas por Pagar)',

                'account_type' => 'liability',
                'nature' => 'credit',
                'level' => 2,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => false,

                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        // 4. Ingresos por Ventas (4100)
        $revenueAccount = Account::firstOrCreate(
            ['code' => '4100'],
            [
                'name' => 'Ingresos por Ventas',

                'account_type' => 'revenue',
                'nature' => 'credit',
                'level' => 2,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => false,

                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        // 5. Gastos Operativos (5100)
        $expenseAccount = Account::firstOrCreate(
            ['code' => '5100'],
            [
                'name' => 'Gastos Operativos',

                'account_type' => 'expense',
                'nature' => 'debit',
                'level' => 2,
                'parent_id' => null,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => false,

                'status' => 'active',
                'metadata' => ['created_by' => 'GLAccountsSeeder', 'module' => 'Finance'],
            ]
        );

        Log::info('Finance GL Accounts seeded', [
            'accounts' => [
                '1020' => $bankAccount->id,
                '1100' => $customerAccount->id,
                '2100' => $supplierAccount->id,
                '4100' => $revenueAccount->id,
                '5100' => $expenseAccount->id,
            ],
        ]);
    }
}
