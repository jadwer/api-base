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
        // Add AP Invoice integration fields to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->bigInteger('ap_invoice_id')->unsigned()->nullable()->after('total_amount');
            $table->enum('invoicing_status', ['pending', 'partial', 'complete'])->default('pending')->after('ap_invoice_id');
            $table->text('invoicing_notes')->nullable()->after('invoicing_status');
            
            $table->index(['ap_invoice_id']);
            $table->index(['invoicing_status']);
        });

        // Add AP Invoice line integration fields to purchase_order_items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->bigInteger('ap_invoice_line_id')->unsigned()->nullable()->after('metadata');
            $table->decimal('invoiced_quantity', 10, 2)->default(0.00)->after('ap_invoice_line_id');
            $table->decimal('invoiced_amount', 10, 2)->default(0.00)->after('invoiced_quantity');
            
            $table->index(['ap_invoice_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['ap_invoice_id']);
            $table->dropIndex(['invoicing_status']);
            $table->dropColumn(['ap_invoice_id', 'invoicing_status', 'invoicing_notes']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex(['ap_invoice_line_id']);
            $table->dropColumn(['ap_invoice_line_id', 'invoiced_quantity', 'invoiced_amount']);
        });
    }
};
