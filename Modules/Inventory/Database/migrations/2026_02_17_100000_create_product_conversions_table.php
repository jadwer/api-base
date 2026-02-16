<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('destination_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('conversion_factor', 15, 4);
            $table->decimal('waste_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_product_id', 'destination_product_id'], 'product_conversions_unique_pair');
            $table->index('source_product_id');
            $table->index('destination_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_conversions');
    }
};
