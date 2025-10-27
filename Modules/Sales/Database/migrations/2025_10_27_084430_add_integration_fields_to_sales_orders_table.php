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
        Schema::table('sales_orders', function (Blueprint $table) {
            // ar_invoice_id and invoicing_status already exist
            // Only add financial_status
            if (!Schema::hasColumn('sales_orders', 'financial_status')) {
                $table->string('financial_status')->default('not_invoiced')->after('invoicing_status');
                $table->index('financial_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'financial_status')) {
                $table->dropColumn('financial_status');
            }
        });
    }
};
