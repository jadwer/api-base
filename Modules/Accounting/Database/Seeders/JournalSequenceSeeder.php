<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding JournalSequence...');
        
        // Create sample JournalSequence records
        JournalSequence::factory()->count(10)->create();

        
        $this->command->info('✅ JournalSequence seeded successfully!');
    }
}
