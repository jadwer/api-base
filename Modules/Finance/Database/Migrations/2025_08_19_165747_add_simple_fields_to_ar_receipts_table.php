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
        Schema::table('ar_receipts', function (Blueprint $table) {
            // F1 Simple model: One receipt per invoice
            $table->foreignId('ar_invoice_id')->nullable()->after('contact_id')
                ->constrained('ar_invoices')->onDelete('restrict');
            $table->string('receipt_number')->nullable()->after('id');
            $table->decimal('applied_amount', 10, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_receipts', function (Blueprint $table) {
            $table->dropForeign(['ar_invoice_id']);
            $table->dropColumn(['ar_invoice_id', 'receipt_number', 'applied_amount']);
        });
    }
};
