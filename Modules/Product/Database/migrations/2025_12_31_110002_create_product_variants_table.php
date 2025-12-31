<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR-M003: Product Variants table migration.
 *
 * Stores product variants with specific attribute combinations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');
            $table->string('sku', 100)->unique();
            $table->string('name', 255)->nullable();    // Auto-generated or custom name
            $table->float('price')->nullable();          // Override product price
            $table->float('cost')->nullable();           // Override product cost
            $table->float('weight')->nullable();         // Variant-specific weight
            $table->string('barcode', 100)->nullable();
            $table->string('img_path', 400)->nullable(); // Variant-specific image
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();        // Additional variant data
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku');
            $table->index('is_active');
            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
