<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing products with img_path to the new product_images table
        $products = DB::table('products')
            ->whereNotNull('img_path')
            ->where('img_path', '!=', '')
            ->select('id', 'img_path')
            ->orderBy('id')
            ->get();

        $now = now();
        $records = [];

        foreach ($products as $product) {
            $records[] = [
                'product_id' => $product->id,
                'file_path' => $product->img_path,
                'alt_text' => null,
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in chunks of 100
            if (count($records) >= 100) {
                DB::table('product_images')->insert($records);
                $records = [];
            }
        }

        // Insert remaining records
        if (!empty($records)) {
            DB::table('product_images')->insert($records);
        }
    }

    public function down(): void
    {
        // Remove all migrated records (those that are primary and were auto-created)
        DB::table('product_images')->truncate();
    }
};
