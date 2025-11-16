<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARInvoice...');

        // Get existing Customer contacts (using Contact model with is_customer flag)
        $customers = \Modules\Contacts\Models\Contact::where('is_customer', true)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No Customer contacts found. Skipping contact_id seeding.');
            return;
        }

        // Create sample ARInvoice records
        // Create ARInvoice records using existing Customer contacts
        $customers->take(5)->each(function ($contact) {
            ARInvoice::factory()
                ->count(rand(1, 3))
                ->create(['contact_id' => $contact->id]);
        });

        // Create some active records
        ARInvoice::factory()->active()->count(5)->create();

        // Create some inactive records
        ARInvoice::factory()->inactive()->count(2)->create();


        $this->command->info('✅ ARInvoice seeded successfully!');
    }
}
