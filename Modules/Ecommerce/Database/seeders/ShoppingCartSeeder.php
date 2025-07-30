<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\User\Models\User;

class ShoppingCartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ShoppingCart...');
        
        // Get existing User records
        $users = \Modules\User\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No User records found. Skipping user_id seeding.');
            return;
        }

        // Create sample ShoppingCart records
        // Create ShoppingCart records using existing User records
        $users->take(5)->each(function ($parent) {
            ShoppingCart::factory()
                ->count(rand(1, 3))
                ->create(['user_id' => $parent->id]);
        });

        // Create some active records
        ShoppingCart::factory()->active()->count(5)->create();

        // Create some inactive records
        ShoppingCart::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ ShoppingCart seeded successfully!');
    }
}
