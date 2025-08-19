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
            PermissionsSeeder::class,
            BankAccountSeeder::class,
            BankStatementSeeder::class,
            BankStatementLineSeeder::class,
            APInvoiceSeeder::class,
            APInvoiceLineSeeder::class,
            APPaymentSeeder::class,
            APInvoicePaymentSeeder::class,
            ARInvoiceSeeder::class,
            ARInvoiceLineSeeder::class,
            ARReceiptSeeder::class,
            ARInvoiceReceiptSeeder::class,
        ]);
        
        $this->command->info('🎉 Finance module seeded successfully!');
    }
}
