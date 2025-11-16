<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;

class ContactsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Contacts module...');

        $this->call([
            PermissionsSeeder::class,
            // ❌ DEMO DATA - Commented for presentation
            // ContactSeeder::class,           // Sample contacts
            // ContactDocumentSeeder::class,   // Sample documents
            // ContactAddressSeeder::class,    // Sample addresses
            // ContactPersonSeeder::class,     // Sample contact persons
        ]);

        $this->command->info('🎉 Contacts module seeded successfully!');
    }
}
