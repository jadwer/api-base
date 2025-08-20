<?php 

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\Supplier;
use Modules\Contacts\Models\Contact;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have suppliers (from Contacts module)
        $supplierContacts = Contact::where('is_supplier', true)->get();
        
        if ($supplierContacts->isEmpty()) {
            // Create supplier contacts if none exist
            $supplierContacts = Contact::factory()
                ->supplier()
                ->count(5)
                ->create();
        }

        // Create purchase orders with recent dates
        $supplierContacts->take(3)->each(function ($contact, $index) {
            $orderDate = match($index) {
                0 => now()->subDays(2),   // 2 días atrás
                1 => now()->subDays(25),  // 25 días atrás  
                2 => now()->subDays(60),  // 60 días atrás
            };

            $purchaseOrder = PurchaseOrder::factory()
                ->for($contact, 'contact')
                ->create([
                    'order_date' => $orderDate,
                    'status' => $index < 2 ? 'pending' : 'received'
                ]);

            // Create 2-5 items per purchase order
            PurchaseOrderItem::factory()
                ->for($purchaseOrder)
                ->count(fake()->numberBetween(2, 5))
                ->create();
        });
    }
}
