<?php 

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

class SalesOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have customer contacts
        $customerContacts = Contact::where('is_customer', true)->get();
        
        if ($customerContacts->isEmpty()) {
            // Create customer contacts if none exist
            $customerContacts = Contact::factory()
                ->customer()
                ->count(8)
                ->create();
        }

        // Ensure we have products for the order items
        $products = Product::all();
        if ($products->isEmpty()) {
            $products = Product::factory()->count(15)->create();
        }

        // Create sales orders with items - more variety than purchases
        $customerContacts->take(6)->each(function ($contact) use ($products) {
            // Create 1-3 sales orders per customer
            $ordersCount = fake()->numberBetween(1, 3);
            
            for ($i = 0; $i < $ordersCount; $i++) {
                $salesOrder = SalesOrder::factory()
                    ->for($contact, 'contact')
                    ->create([
                        'order_date' => fake()->dateTimeBetween('-6 months', 'now'),
                        'status' => fake()->randomElement(['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
                        'metadata' => [
                            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
                            'delivery_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
                            'source' => fake()->randomElement(['web', 'phone', 'email', 'in_person']),
                        ]
                    ]);

                // Create 1-8 items per sales order (more varied than purchases)
                $itemsCount = fake()->numberBetween(1, 8);
                $selectedProducts = $products->random($itemsCount);
                
                foreach ($selectedProducts as $product) {
                    $quantity = fake()->numberBetween(1, 50);
                    $unitPrice = fake()->randomFloat(2, 10, 5000);
                    $discount = fake()->optional(0.3)->randomFloat(2, 0, $unitPrice * $quantity * 0.25); // 30% chance of discount, max 25%
                    $total = ($quantity * $unitPrice) - ($discount ?? 0);
                    
                    SalesOrderItem::factory()
                        ->for($salesOrder)
                        ->for($product)
                        ->create([
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount' => $discount ?? 0,
                            'total' => $total,
                        ]);
                }
            }
        });

        // Create some additional high-value orders for reporting demos
        $vipCustomers = $customerContacts->take(2);
        foreach ($vipCustomers as $customer) {
            $bigOrder = SalesOrder::factory()
                ->for($customer, 'contact')
                ->create([
                    'status' => 'delivered',
                    'order_date' => fake()->dateTimeBetween('-3 months', '-1 month'),
                    'metadata' => [
                        'priority' => 'high',
                        'delivery_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
                        'source' => 'enterprise_client',
                        'sales_rep' => fake()->name(),
                        'commission_rate' => 5.0,
                        'payment_terms' => 'net_30'
                    ]
                ]);

            // High-value items
            $premiumProducts = $products->random(3);
            foreach ($premiumProducts as $product) {
                $quantity = fake()->numberBetween(10, 100);
                $unitPrice = fake()->randomFloat(2, 500, 10000);
                $discount = fake()->randomFloat(2, $unitPrice * $quantity * 0.05, $unitPrice * $quantity * 0.15); // 5-15% discount
                $total = ($quantity * $unitPrice) - $discount;
                
                SalesOrderItem::factory()
                    ->for($bigOrder)
                    ->for($product)
                    ->create([
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'total' => $total,
                    ]);
            }
        }
    }
}