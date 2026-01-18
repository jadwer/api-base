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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->float('quantity')->default(1);
            $table->decimal('unit_price', 15, 2); // Precio original del producto
            $table->decimal('quoted_price', 15, 2); // Precio cotizado (editable)
            $table->decimal('discount_percentage', 5, 2)->default(0); // Descuento en porcentaje
            $table->decimal('discount_amount', 15, 2)->default(0); // Descuento calculado
            $table->decimal('tax_rate', 5, 2)->default(16); // IVA por defecto en México
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('product_name')->nullable(); // Snapshot del nombre del producto
            $table->string('product_sku')->nullable(); // Snapshot del SKU
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('quote_id');
            $table->index('product_id');
            $table->index(['quote_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
