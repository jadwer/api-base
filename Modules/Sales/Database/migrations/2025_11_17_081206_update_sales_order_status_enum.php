<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SA-007: Update sales order status ENUM to match OrderStatusService states
     *
     * Current: 6 states (draft, confirmed, processing, shipped, delivered, cancelled)
     * New: 10 states (adding: pending, completed, returned, refunded)
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'draft',
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'completed',
                'cancelled',
                'returned',
                'refunded'
            ) DEFAULT 'draft'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'draft',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ) DEFAULT 'draft'
        ");
    }
};
