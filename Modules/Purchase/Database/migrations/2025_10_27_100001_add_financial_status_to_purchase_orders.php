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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'financial_status')) {
                $table->string('financial_status')->default('not_invoiced')->after('status');
                $table->index('financial_status');
            }

            if (!Schema::hasColumn('purchase_orders', 'ap_invoice_id')) {
                $table->unsignedBigInteger('ap_invoice_id')->nullable()->after('financial_status');
                $table->index('ap_invoice_id');
                $table->foreign('ap_invoice_id')->references('id')->on('ap_invoices')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'ap_invoice_id')) {
                $table->dropForeign(['ap_invoice_id']);
                $table->dropColumn('ap_invoice_id');
            }

            if (Schema::hasColumn('purchase_orders', 'financial_status')) {
                $table->dropColumn('financial_status');
            }
        });
    }
};
