<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ContactPerson...');
        
        // Get existing contacts to associate persons with
        $contacts = \Modules\Contacts\Models\Contact::all();
        
        if ($contacts->isEmpty()) {
            $this->command->warn('⚠️ No contacts found. Skipping ContactPerson seeding.');
            return;
        }

        // Create 1-3 persons per contact
        $contacts->each(function ($contact) {
            $personCount = rand(1, 3);
            ContactPerson::factory()
                ->count($personCount)
                ->for($contact)
                ->create();
        });
        
        $this->command->info('✅ ContactPerson seeded successfully!');
    }
}
