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
            GLAccountsSeeder::class,       // ✅ GL account mappings (ESSENTIAL)
            PaymentMethodsSeeder::class,   // ✅ Payment methods (ESSENTIAL)

            // ❌ DEMO DATA - Commented for presentation
            // BankAccountSeeder::class,            // Sample bank accounts
            // ARInvoiceSeeder::class,              // Sample AR invoices
            // APInvoiceSeeder::class,              // Sample AP invoices
            // PaymentSeeder::class,                // Sample payments
            // PaymentApplicationSeeder::class,     // Sample payment applications
        ]);

        $this->command->info('🎉 Finance module seeded successfully!');
    }
}
