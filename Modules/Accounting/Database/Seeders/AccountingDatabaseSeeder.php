<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Accounting module...');

        $this->call([
            PermissionsSeeder::class,
            CatalogoCuentasMexicanoSeeder::class, // ✅ Mexican Chart of Accounts (ESSENTIAL)
            FiscalPeriodSeeder::class,            // ✅ Fiscal periods (ESSENTIAL)
            JournalSeeder::class,                 // ✅ Journal types (ESSENTIAL)
            JournalSequenceSeeder::class,         // ✅ Sequence numbering (ESSENTIAL)
            ExchangeRateSeeder::class,            // ✅ Exchange rates (ESSENTIAL)
            // ✅ DEMO DATA - Enabled for presentation
            JournalEntrySeeder::class,         // Sample journal entries
            // JournalLineSeeder::class,          // Skipped - created by JournalEntrySeeder
            AccountBalanceSeeder::class,       // Sample account balances
            // IdempotencyKeySeeder::class,       // Skipped - internal tracking
            AccountMappingSeeder::class,       // Sample account mappings
        ]);

        $this->command->info('🎉 Accounting module seeded successfully!');
    }
}
