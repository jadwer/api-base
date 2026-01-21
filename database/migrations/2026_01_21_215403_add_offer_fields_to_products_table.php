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
        Schema::table('products', function (Blueprint $table) {
            // Offer/Sale fields
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('price'); // Original price before discount
            $table->boolean('is_on_sale')->default(false)->after('compare_at_price');
            $table->timestamp('sale_starts_at')->nullable()->after('is_on_sale');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
            $table->string('sale_badge', 50)->nullable()->after('sale_ends_at'); // e.g., "OFERTA", "ÚLTIMAS PIEZAS"

            // Index for filtering products on sale
            $table->index(['is_on_sale', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_on_sale', 'is_active']);
            $table->dropColumn([
                'compare_at_price',
                'is_on_sale',
                'sale_starts_at',
                'sale_ends_at',
                'sale_badge',
            ]);
        });
    }
};
