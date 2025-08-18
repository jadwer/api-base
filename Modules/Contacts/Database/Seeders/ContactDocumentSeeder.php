<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ContactDocument...');
        
        // Get existing contacts to associate documents with
        $contacts = \Modules\Contacts\Models\Contact::all();
        
        if ($contacts->isEmpty()) {
            $this->command->warn('⚠️ No contacts found. Skipping ContactDocument seeding.');
            return;
        }

        // Create 1-3 documents per contact
        $contacts->each(function ($contact) {
            $documentCount = rand(1, 3);
            ContactDocument::factory()
                ->count($documentCount)
                ->for($contact)
                ->create([
                    'uploaded_by' => 1, // Assume admin user uploaded
                    'verified_by' => rand(0, 1) ? 1 : null, // Some verified by admin
                ]);
        });
        
        $this->command->info('✅ ContactDocument seeded successfully!');
    }
}
