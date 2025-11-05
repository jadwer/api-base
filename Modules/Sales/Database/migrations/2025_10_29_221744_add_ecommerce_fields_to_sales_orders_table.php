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
        Schema::table('sales_orders', function (Blueprint $table) {
            // E-commerce specific fields
            $table->enum('order_source', ['ecommerce', 'manual', 'api', 'import'])->default('manual')->after('status');
            $table->foreignId('checkout_session_id')->nullable()->constrained('checkout_sessions')->onDelete('set null')->after('contact_id');
            $table->string('tracking_number')->nullable()->after('order_source');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->json('shipping_address')->nullable()->after('notes');
            $table->json('billing_address')->nullable()->after('shipping_address');

            // Indexes
            $table->index('checkout_session_id');
            $table->index('order_source');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['checkout_session_id']);
            $table->dropIndex(['checkout_session_id']);
            $table->dropIndex(['order_source']);
            $table->dropIndex(['tracking_number']);

            $table->dropColumn([
                'order_source',
                'checkout_session_id',
                'tracking_number',
                'tracking_url',
                'shipping_address',
                'billing_address',
            ]);
        });
    }
};
