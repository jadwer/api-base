<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contacts\Models\Contact;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Contact...');
        
        // Create sample Contact records
        Contact::factory()->count(10)->create();

        // Create some active records
        Contact::factory()->active()->count(5)->create();

        // Create some inactive records
        Contact::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ Contact seeded successfully!');
    }
}
