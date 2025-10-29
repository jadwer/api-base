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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->nullable()->constrained('checkout_sessions')->onDelete('set null');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->foreignId('ar_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('set null');

            // Payment Gateway Information
            $table->string('transaction_id')->unique(); // from payment gateway
            $table->string('payment_gateway'); // e.g., "stripe", "paypal", "mock"
            $table->string('payment_method'); // e.g., "card", "bank_transfer"

            $table->enum('status', [
                'pending',
                'authorized',
                'captured',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');

            // Financial Information
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MXN');

            // Gateway Response
            $table->json('gateway_response')->nullable(); // Full response from gateway
            $table->text('error_message')->nullable();

            // Timestamps
            $table->timestamp('processed_at')->nullable();

            // Additional Information
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('checkout_session_id');
            $table->index('sales_order_id');
            $table->index('transaction_id');
            $table->index('payment_gateway');
            $table->index('status');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
