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
            IdempotencyKeySeeder::class,
            AccountMappingSeeder::class,
            AccountBalanceSeeder::class,
            ExchangeRatePolicySeeder::class,
            AuditLogSeeder::class,
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
            JournalSeeder::class,
            JournalSequenceSeeder::class,
            JournalEntrySeeder::class,
            JournalLineSeeder::class,
            ExchangeRateSeeder::class,
        ]);
        
        $this->command->info('🎉 Accounting module seeded successfully!');
    }
}
