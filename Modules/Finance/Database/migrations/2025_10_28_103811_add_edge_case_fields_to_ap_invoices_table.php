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
        Schema::table('ap_invoices', function (Blueprint $table) {
            // Void support
            $table->timestamp('voided_at')->nullable()->after('status');
            $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null')->after('voided_at');
            $table->text('void_reason')->nullable()->after('voided_by_id');

            // Replacement invoice support
            $table->foreignId('replaces_invoice_id')->nullable()->constrained('ap_invoices')->onDelete('restrict')->after('void_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['voided_by_id']);
            $table->dropForeign(['replaces_invoice_id']);
            $table->dropColumn(['voided_at', 'voided_by_id', 'void_reason', 'replaces_invoice_id']);
        });
    }
};
