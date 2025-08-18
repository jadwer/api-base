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
            ContactSeeder::class,
            ContactDocumentSeeder::class,
            ContactAddressSeeder::class,
            ContactPersonSeeder::class,
        ]);
        
        $this->command->info('🎉 Contacts module seeded successfully!');
    }
}
