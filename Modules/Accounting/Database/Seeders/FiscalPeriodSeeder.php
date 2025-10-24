<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding FiscalPeriod...');

        // Create 2025 fiscal periods (for integration tests)
        for ($month = 1; $month <= 12; $month++) {
            $startDate = \Carbon\Carbon::create(2025, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            FiscalPeriod::firstOrCreate(
                [
                    'year' => 2025,
                    'month' => $month,
                ],
                [
                    'name' => sprintf('2025-%02d', $month),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'status' => 'open',
                    'closed_at' => null,
                    'closed_by_id' => null,
                    'closing_entry_id' => null,
                    'metadata' => [],
                ]
            );
        }

        // Create sample FiscalPeriod records for other years
        FiscalPeriod::factory()->count(10)->create();

        // Create some open records
        FiscalPeriod::factory()->open()->count(5)->create();

        // Create some closed records
        FiscalPeriod::factory()->closed()->count(2)->create();


        $this->command->info('✅ FiscalPeriod seeded successfully!');
    }
}
