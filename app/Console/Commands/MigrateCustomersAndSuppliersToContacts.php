<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Customer;
use Modules\Purchase\Models\Supplier;

class MigrateCustomersAndSuppliersToContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:customers-suppliers-to-contacts {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Customers and Suppliers to unified Contacts table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🧪 Running in DRY RUN mode - no changes will be made');
        }

        $this->info('🚀 Starting migration from Customers and Suppliers to Contacts...');
        
        DB::transaction(function () use ($dryRun) {
            // Migrate Customers
            $this->migrateCustomers($dryRun);
            
            // Migrate Suppliers
            $this->migrateSuppliers($dryRun);
        });

        $this->info('✅ Migration completed successfully!');
        
        if (!$dryRun) {
            $this->warn('⚠️ Remember to update SalesOrders and PurchaseOrders to use the new contact_id relationships');
        }
    }

    private function migrateCustomers(bool $dryRun): void
    {
        if (!class_exists(Customer::class)) {
            $this->warn('Customer model not found - skipping customers migration');
            return;
        }

        $customers = Customer::all();
        $this->info("📊 Found {$customers->count()} customers to migrate");

        foreach ($customers as $customer) {
            $contactData = [
                'type' => 'company',
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->is_active ? 'active' : 'inactive',
                'is_customer' => true,
                'is_supplier' => false,
                'credit_limit' => $customer->credit_limit ?? 0.00,
                'current_credit' => $customer->current_credit ?? 0.00,
                'classification' => $customer->classification ?? 'standard',
                'notes' => "Migrated from Customer ID: {$customer->id}",
                'metadata' => $customer->metadata ?? [],
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ];

            if (!$dryRun) {
                $contact = Contact::create($contactData);
                $this->line("  ✓ Migrated customer '{$customer->name}' -> Contact ID: {$contact->id}");
                
                // Update sales_orders to use new contact_id
                DB::table('sales_orders')
                    ->where('customer_id', $customer->id)
                    ->update(['contact_id' => $contact->id]);
            } else {
                $this->line("  📋 Would migrate customer '{$customer->name}'");
            }
        }
    }

    private function migrateSuppliers(bool $dryRun): void
    {
        if (!class_exists(Supplier::class)) {
            $this->warn('Supplier model not found - skipping suppliers migration');
            return;
        }

        $suppliers = Supplier::all();
        $this->info("📊 Found {$suppliers->count()} suppliers to migrate");

        foreach ($suppliers as $supplier) {
            $contactData = [
                'type' => 'company',
                'name' => $supplier->name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'tax_id' => $supplier->rfc ?? null,
                'status' => $supplier->is_active ? 'active' : 'inactive',
                'is_customer' => false,
                'is_supplier' => true,
                'notes' => "Migrated from Supplier ID: {$supplier->id}",
                'metadata' => [],
                'created_at' => $supplier->created_at,
                'updated_at' => $supplier->updated_at,
            ];

            if (!$dryRun) {
                $contact = Contact::create($contactData);
                $this->line("  ✓ Migrated supplier '{$supplier->name}' -> Contact ID: {$contact->id}");
                
                // Update purchase_orders to use new contact_id
                DB::table('purchase_orders')
                    ->where('supplier_id', $supplier->id)
                    ->update(['contact_id' => $contact->id]);
            } else {
                $this->line("  📋 Would migrate supplier '{$supplier->name}'");
            }
        }
    }
}
