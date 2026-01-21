<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add assigned_to (seller/employee) to sales_orders for Sales by Employee reports.
     * Phase 13: Advanced Reports - Sales by Employee requirement.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('contact_id')
                ->constrained('users')
                ->onDelete('set null');

            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
