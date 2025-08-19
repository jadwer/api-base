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
        Schema::table('ap_payments', function (Blueprint $table) {
            // F1 Simple model: One payment per invoice
            $table->foreignId('ap_invoice_id')->nullable()->after('contact_id')
                ->constrained('ap_invoices')->onDelete('restrict');
            $table->string('payment_number')->nullable()->after('id');
            $table->decimal('applied_amount', 10, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_payments', function (Blueprint $table) {
            $table->dropForeign(['ap_invoice_id']);
            $table->dropColumn(['ap_invoice_id', 'payment_number', 'applied_amount']);
        });
    }
};
