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
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->constrained('checkout_sessions')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stock')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');

            $table->decimal('quantity_reserved', 10, 2);

            $table->enum('status', [
                'active',
                'released',
                'fulfilled',
                'expired'
            ])->default('active');

            // Timestamps
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();

            // Additional Information
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('checkout_session_id');
            $table->index('stock_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
