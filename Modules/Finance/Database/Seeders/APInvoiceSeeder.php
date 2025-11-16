<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APInvoice;

class APInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APInvoice...');

        // Get existing Supplier contacts (using Contact model with is_supplier flag)
        $suppliers = \Modules\Contacts\Models\Contact::where('is_supplier', true)->get();

        if ($suppliers->isEmpty()) {
            $this->command->warn('No Supplier contacts found. Skipping contact_id seeding.');
            return;
        }

        // Create sample APInvoice records
        // Create APInvoice records using existing Supplier contacts
        $suppliers->take(5)->each(function ($contact) {
            APInvoice::factory()
                ->count(rand(1, 3))
                ->create(['contact_id' => $contact->id]);
        });

        // Create some active records
        APInvoice::factory()->active()->count(5)->create();

        // Create some inactive records
        APInvoice::factory()->inactive()->count(2)->create();


        $this->command->info('✅ APInvoice seeded successfully!');
    }
}
