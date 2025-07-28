<?php 

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\Supplier;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have suppliers first
        if (Supplier::count() === 0) {
            Supplier::factory()->count(5)->create();
        }

        // Create purchase orders with items
        Supplier::inRandomOrder()->take(3)->get()->each(function ($supplier) {
            $purchaseOrder = PurchaseOrder::factory()
                ->for($supplier)
                ->create();

            // Create 2-5 items per purchase order
            PurchaseOrderItem::factory()
                ->for($purchaseOrder)
                ->count(fake()->numberBetween(2, 5))
                ->create();
        });
    }
}
