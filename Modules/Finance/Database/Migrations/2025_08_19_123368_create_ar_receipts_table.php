<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ar_receipts", function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->date('receipt_date');
            $table->string('payment_method');
            $table->string('currency')->nullable();
            $table->decimal('amount', 10, 2);
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ar_receipts");
    }
};