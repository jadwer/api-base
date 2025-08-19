<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ar_invoice_receipts", function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_invoice_id')->constrained('ar_invoices')->onDelete('restrict');
            $table->foreignId('ar_receipt_id')->constrained('ar_receipts')->onDelete('restrict');
            $table->decimal('amount_applied', 10, 2);
            $table->date('applied_at');
            $table->decimal('exchange_rate_at_apply', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ar_invoice_receipts");
    }
};