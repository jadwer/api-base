<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make stock_id nullable to support batch-based reservations (FEFO strategy)
     */
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['stock_id']);

            // Make stock_id nullable
            $table->foreignId('stock_id')->nullable()->change();

            // Re-add foreign key constraint
            $table->foreign('stock_id')
                ->references('id')
                ->on('stock')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['stock_id']);

            // Make stock_id non-nullable again
            $table->foreignId('stock_id')->nullable(false)->change();

            // Re-add foreign key constraint
            $table->foreign('stock_id')
                ->references('id')
                ->on('stock')
                ->onDelete('cascade');
        });
    }
};
