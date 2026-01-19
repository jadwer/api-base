<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add order_number to purchase_orders for unique identification.
     * Phase 12: PurchaseOrder needs a human-readable number like SalesOrder.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->unique()->after('id');
            $table->foreignId('quote_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('quotes')
                ->onDelete('set null');

            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn(['order_number', 'quote_id']);
        });
    }
};
