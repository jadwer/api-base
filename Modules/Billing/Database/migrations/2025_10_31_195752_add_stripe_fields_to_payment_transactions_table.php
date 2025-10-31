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
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Add Stripe-specific fields
            $table->string('payment_intent_id', 255)->nullable()->unique()->after('ar_invoice_id');
            $table->string('client_secret', 255)->nullable()->after('payment_intent_id');
            $table->string('card_brand', 50)->nullable()->after('payment_method');
            $table->string('card_last4', 4)->nullable()->after('card_brand');

            // Add event-specific timestamps
            $table->timestamp('authorized_at')->nullable()->after('error_message');
            $table->timestamp('captured_at')->nullable()->after('authorized_at');
            $table->timestamp('failed_at')->nullable()->after('captured_at');
            $table->timestamp('refunded_at')->nullable()->after('failed_at');

            // Rename payment_gateway to gateway (more concise)
            $table->renameColumn('payment_gateway', 'gateway');

            // Make transaction_id nullable (payment_intent_id is the primary identifier for Stripe)
            $table->string('transaction_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Reverse the changes
            $table->dropColumn([
                'payment_intent_id',
                'client_secret',
                'card_brand',
                'card_last4',
                'authorized_at',
                'captured_at',
                'failed_at',
                'refunded_at',
            ]);

            $table->renameColumn('gateway', 'payment_gateway');
            $table->string('transaction_id')->unique()->change();
        });
    }
};
