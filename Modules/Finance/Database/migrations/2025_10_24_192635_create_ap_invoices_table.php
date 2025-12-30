<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ap_invoices", function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict'); // Consolidado: era supplier_id
            $table->unsignedBigInteger('purchase_order_id')->nullable(); // FK agregada en migración separada
            $table->string('currency')->default('MXN');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->nullable()->default(0);
            $table->string('status')->default('draft');

            // Reconciliation fields (consolidado)
            $table->enum('reconciliation_status', ['pending', 'matched', 'discrepancy', 'approved'])->default('pending');
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('reconciliation_notes')->nullable();
            $table->json('discrepancies')->nullable();

            // Edge case fields (consolidado)
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('void_reason')->nullable();
            $table->foreignId('replaces_invoice_id')->nullable()->constrained('ap_invoices')->onDelete('restrict');

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->onDelete('restrict'); // Consolidado
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamps();

            // Performance indexes
            $table->index('purchase_order_id');
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
        Schema::dropIfExists("ap_invoices");
    }
};