<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\AuditLog;
use Modules\User\Models\User;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding AuditLog...');
        
        // Get existing User records
        $users = \Modules\User\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No User records found. Skipping user_id seeding.');
            return;
        }

        // Create sample AuditLog records
        // Create AuditLog records using existing User records
        $users->take(5)->each(function ($parent) {
            AuditLog::factory()
                ->count(rand(1, 3))
                ->create(['user_id' => $parent->id]);
        });

        
        $this->command->info('✅ AuditLog seeded successfully!');
    }
}
