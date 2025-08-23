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
        // Add AR Invoice integration fields to sales_orders
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->bigInteger('ar_invoice_id')->unsigned()->nullable()->after('total_amount');
            $table->enum('invoicing_status', ['pending', 'partial', 'complete'])->default('pending')->after('ar_invoice_id');
            $table->text('invoicing_notes')->nullable()->after('invoicing_status');
            
            $table->index(['ar_invoice_id']);
            $table->index(['invoicing_status']);
        });

        // Add AR Invoice line integration fields to sales_order_items  
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->bigInteger('ar_invoice_line_id')->unsigned()->nullable()->after('metadata');
            $table->decimal('invoiced_quantity', 10, 2)->default(0.00)->after('ar_invoice_line_id');
            $table->decimal('invoiced_amount', 10, 2)->default(0.00)->after('invoiced_quantity');
            
            $table->index(['ar_invoice_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['ar_invoice_id']);
            $table->dropIndex(['invoicing_status']);
            $table->dropColumn(['ar_invoice_id', 'invoicing_status', 'invoicing_notes']);
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['ar_invoice_line_id']);
            $table->dropColumn(['ar_invoice_line_id', 'invoiced_quantity', 'invoiced_amount']);
        });
    }
};
