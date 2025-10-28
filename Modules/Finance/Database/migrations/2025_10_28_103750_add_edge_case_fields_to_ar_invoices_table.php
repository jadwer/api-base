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
        Schema::table('ar_invoices', function (Blueprint $table) {
            // Refund support
            $table->boolean('is_refund')->default(false)->after('status');
            $table->foreignId('refund_of_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('restrict')->after('is_refund');

            // Void support
            $table->timestamp('voided_at')->nullable()->after('refund_of_invoice_id');
            $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null')->after('voided_at');
            $table->text('void_reason')->nullable()->after('voided_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->dropForeign(['refund_of_invoice_id']);
            $table->dropForeign(['voided_by_id']);
            $table->dropColumn(['is_refund', 'refund_of_invoice_id', 'voided_at', 'voided_by_id', 'void_reason']);
        });
    }
};
