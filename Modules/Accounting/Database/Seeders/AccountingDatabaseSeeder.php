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
            // IdempotencyKeySeeder::class, // Commented for performance - not needed for tests
            // AccountMappingSeeder::class, // Commented for performance - not needed for tests
            // AccountBalanceSeeder::class, // Commented for performance - creates test data
            // ExchangeRatePolicySeeder::class, // Commented for performance - not needed for tests
            // AuditLogSeeder::class, // Commented for performance - creates test data
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
            JournalSeeder::class,
            JournalSequenceSeeder::class,
            // JournalEntrySeeder::class, // Commented for performance - creates 17 test records
            JournalLineSeeder::class, // Already skipped internally
            ExchangeRateSeeder::class, // Needed for currency conversion
        ]);
        
        $this->command->info('🎉 Accounting module seeded successfully!');
    }
}
