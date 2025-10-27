<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;

class FinanceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Finance module...');

        $this->call([
            // Primero: Permisos
            PermissionsSeeder::class,
            FinanceAssignPermissionsSeeder::class,

            // Luego: Configuración básica (GL Accounts y Payment Methods)
            GLAccountsSeeder::class,
            PaymentMethodsSeeder::class,

            // Luego: Datos de prueba (solo si es necesario)
            // ARInvoiceSeeder::class,
            // APInvoiceSeeder::class,
            // PaymentSeeder::class,
            // PaymentApplicationSeeder::class,
            // BankAccountSeeder::class,
        ]);

        $this->command->info('🎉 Finance module seeded successfully!');
    }
}
