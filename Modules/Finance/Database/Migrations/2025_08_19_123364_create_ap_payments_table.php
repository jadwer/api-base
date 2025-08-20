<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ap_payments", function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->nullable();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('ap_invoice_id')->nullable()->constrained('ap_invoices')->onDelete('restrict');
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('currency')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('applied_amount', 10, 2)->nullable();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ap_payments");
    }
};