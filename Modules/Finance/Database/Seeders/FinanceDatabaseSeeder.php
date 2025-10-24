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
            ARInvoiceSeeder::class,
            APInvoiceSeeder::class,
            PaymentSeeder::class,
            PaymentApplicationSeeder::class,
            BankAccountSeeder::class,
            PaymentMethodSeeder::class,
        ]);
        
        $this->command->info('🎉 Finance module seeded successfully!');
    }
}
