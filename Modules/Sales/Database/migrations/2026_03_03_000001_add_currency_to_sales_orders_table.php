<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('MXN')->after('discount_total');
            $table->decimal('exchange_rate_used', 12, 6)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate_used']);
        });
    }
};
