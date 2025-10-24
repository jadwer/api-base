<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\IdempotencyKey;
use Modules\User\Models\User;

class IdempotencyKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding IdempotencyKey...');
        
        // Get existing User records
        $users = \Modules\User\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No User records found. Skipping user_id seeding.');
            return;
        }

        // Create sample IdempotencyKey records
        // Create IdempotencyKey records using existing User records
        $users->take(5)->each(function ($parent) {
            IdempotencyKey::factory()
                ->count(rand(1, 3))
                ->create(['user_id' => $parent->id]);
        });

        // Create some processing records
        IdempotencyKey::factory()->processing()->count(5)->create();

        // Create some completed records
        IdempotencyKey::factory()->completed()->count(2)->create();

        
        $this->command->info('✅ IdempotencyKey seeded successfully!');
    }
}
