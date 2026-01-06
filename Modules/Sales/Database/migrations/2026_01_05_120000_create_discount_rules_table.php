<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA-M003: Automatic Discount Rules
 *
 * Discount rules can be applied automatically based on:
 * - Order total (volume discounts)
 * - Product/Category (product-specific discounts)
 * - Customer classification (loyalty discounts)
 * - Date range (promotional discounts)
 * - Quantity (bulk discounts)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();

            // Rule identification
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Rule type: percentage, fixed_amount, buy_x_get_y
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'buy_x_get_y'])
                  ->default('percentage');

            // Discount value (percentage 0-100 or fixed amount)
            $table->decimal('discount_value', 10, 2);

            // For buy_x_get_y: buy X quantity, get Y free
            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();

            // Applicability conditions
            $table->enum('applies_to', ['order', 'product', 'category', 'customer'])
                  ->default('order');

            // Minimum requirements
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->integer('min_quantity')->nullable();

            // Maximum discount cap (for percentage discounts)
            $table->decimal('max_discount_amount', 15, 2)->nullable();

            // Scope restrictions (JSON arrays of IDs)
            $table->json('product_ids')->nullable();      // Specific products
            $table->json('category_ids')->nullable();     // Product categories
            $table->json('customer_ids')->nullable();     // Specific customers
            $table->json('customer_classifications')->nullable(); // premium, standard, basic

            // Validity period
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Usage limits
            $table->integer('usage_limit')->nullable();    // Max total uses
            $table->integer('usage_per_customer')->nullable(); // Max per customer
            $table->integer('current_usage')->default(0);

            // Priority (higher = applied first)
            $table->integer('priority')->default(0);

            // Can be combined with other discounts?
            $table->boolean('is_combinable')->default(true);

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
            $table->index('applies_to');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
