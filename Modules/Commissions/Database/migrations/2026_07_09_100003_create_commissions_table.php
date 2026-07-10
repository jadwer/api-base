<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS5 Commissions table.
 *
 * UNIQUE(sales_order_id, user_id) is the idempotency guarantee: the observer
 * and listeners always updateOrCreate against that key, so replays never
 * duplicate rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('ar_invoice_id')->nullable()->constrained('ar_invoices')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->restrictOnDelete();
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'earned', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->unique(['sales_order_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index('earned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
