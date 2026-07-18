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

        // Fase 2.7 (hallazgo del test de invariante de compra): antes solo se
        // sembraba 2025 fijo. Con la fecha real ya en otro anio, TODO posting GL
        // fallaba con "No open fiscal period found" y los listeners de Finance lo
        // tragaban en silencio (sin APInvoice y sin senal). Se siembran el anio
        // pasado, el actual y el siguiente, calculados de la fecha real.
        $years = [now()->year - 1, now()->year, now()->year + 1];
        foreach ($years as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = \Carbon\Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();

                FiscalPeriod::firstOrCreate(
                    [
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'name' => sprintf('%d-%02d', $year, $month),
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
