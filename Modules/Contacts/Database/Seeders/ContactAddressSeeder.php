<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ContactAddress...');
        
        // Get existing contacts to associate addresses with
        $contacts = \Modules\Contacts\Models\Contact::all();
        
        if ($contacts->isEmpty()) {
            $this->command->warn('⚠️ No contacts found. Skipping ContactAddress seeding.');
            return;
        }

        // Create 1-2 addresses per contact
        $contacts->each(function ($contact) {
            $addressCount = rand(1, 2);
            ContactAddress::factory()
                ->count($addressCount)
                ->for($contact)
                ->create();
        });
        
        $this->command->info('✅ ContactAddress seeded successfully!');
    }
}
