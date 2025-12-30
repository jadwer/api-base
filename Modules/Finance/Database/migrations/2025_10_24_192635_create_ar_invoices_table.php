<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ar_invoices", function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict'); // Consolidado: era customer_id
            $table->unsignedBigInteger('sales_order_id')->nullable(); // FK agregada en migración separada
            $table->string('currency')->default('MXN');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->nullable()->default(0);
            $table->date('paid_date')->nullable(); // Consolidado
            $table->string('status')->default('draft');

            // Edge case fields (consolidado)
            $table->boolean('is_refund')->default(false);
            $table->foreignId('refund_of_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('restrict');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('void_reason')->nullable();

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->onDelete('restrict'); // Consolidado
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamps();

            // Performance indexes
            $table->index('sales_order_id');
            $table->index('contact_id');
            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index(['contact_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ar_invoices");
    }
};