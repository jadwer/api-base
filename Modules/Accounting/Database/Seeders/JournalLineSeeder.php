<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\JournalLine;

class JournalLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding JournalLine...');
        
        // Create sample JournalLine records
        JournalLine::factory()->count(10)->create();

        
        $this->command->info('✅ JournalLine seeded successfully!');
    }
}
