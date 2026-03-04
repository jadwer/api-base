<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('original_currency_code', 3)->nullable()->after('total');
            $table->decimal('original_unit_price', 15, 2)->nullable()->after('original_currency_code');
            $table->decimal('exchange_rate_used', 12, 6)->nullable()->after('original_unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['original_currency_code', 'original_unit_price', 'exchange_rate_used']);
        });
    }
};
