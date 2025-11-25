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
        // Drop the old status column
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Recreate with all 10 status values
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'pending',      // NEW
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'completed',    // NEW
                'cancelled',
                'returned',     // NEW
                'refunded'      // NEW
            ])->default('draft')->after('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the updated status column
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Restore original 6 values
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ])->default('draft')->after('order_number');
        });
    }
};
