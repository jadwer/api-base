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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->date('order_date');
            $table->enum('status', ['pending', 'approved', 'received', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();

            // Finance integration (consolidado)
            $table->string('financial_status')->default('not_invoiced');
            $table->unsignedBigInteger('ap_invoice_id')->nullable(); // FK agregada en migración separada
            $table->enum('invoicing_status', ['not_invoiced', 'partial', 'invoiced'])->default('not_invoiced');
            $table->text('invoicing_notes')->nullable();

            // Approval workflow (consolidado)
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'not_required'])->default('not_required');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('contact_id');
            $table->index('status');
            $table->index('order_date');
            $table->index(['contact_id', 'status']);
            $table->index('invoicing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
