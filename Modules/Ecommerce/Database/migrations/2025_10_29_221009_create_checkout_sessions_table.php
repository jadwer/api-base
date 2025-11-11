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
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_cart_id')->constrained('shopping_carts')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('status', [
                'initiated',
                'payment_pending',
                'payment_confirmed',
                'completed',
                'failed',
                'expired'
            ])->default('initiated');

            $table->enum('step', [
                'address',
                'shipping',
                'payment',
                'confirmation'
            ])->default('address');

            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->onDelete('set null');

            // Address Information
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            // Contact Information
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Payment Information
            $table->string('payment_method')->nullable(); // e.g., "stripe", "paypal"
            $table->string('payment_intent_id')->nullable(); // from payment gateway

            // Financial Breakdown
            $table->decimal('subtotal_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('MXN');

            // Timestamps
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Additional Information
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('shopping_cart_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_intent_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
