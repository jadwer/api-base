<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("cart_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_cart_id')->constrained('shopping_carts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity');
            $table->decimal('unit_price');
            $table->decimal('original_price');
            $table->decimal('discount_percent');
            $table->decimal('discount_amount');
            $table->decimal('subtotal');
            $table->decimal('tax_rate');
            $table->decimal('tax_amount');
            $table->decimal('total');
            $table->json('metadata')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("cart_items");
    }
};