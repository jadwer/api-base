<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_id')->constrained('product_comparisons')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index('comparison_id');
            $table->index('product_id');
            $table->unique(['comparison_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_comparison_items');
    }
};
