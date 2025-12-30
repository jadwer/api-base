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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2)->default(0);
            $table->json('metadata')->nullable();

            // Receiving (consolidado)
            $table->decimal('received_quantity', 15, 4)->default(0);

            // Finance integration (consolidado)
            $table->decimal('invoiced_quantity', 15, 4)->default(0);
            $table->decimal('invoiced_amount', 15, 4)->default(0);

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');

            // Performance indexes
            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
