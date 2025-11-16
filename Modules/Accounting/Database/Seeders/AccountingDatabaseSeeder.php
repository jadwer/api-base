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
            // ❌ DEMO DATA - Commented for presentation
            // JournalEntrySeeder::class,         // Sample journal entries
            // JournalLineSeeder::class,          // Sample journal lines (skipped internally)
            // AccountBalanceSeeder::class,       // Sample account balances
            // IdempotencyKeySeeder::class,       // Sample idempotency tracking
            // AccountMappingSeeder::class,       // Sample account mappings
        ]);

        $this->command->info('🎉 Accounting module seeded successfully!');
    }
}
