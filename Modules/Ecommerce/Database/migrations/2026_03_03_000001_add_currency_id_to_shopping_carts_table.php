<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_carts', function (Blueprint $table) {
            $table->foreignId('currency_id')
                ->nullable()
                ->after('currency')
                ->constrained('currencies')
                ->nullOnDelete();
        });

        // Populate currency_id from existing currency string
        DB::statement('
            UPDATE shopping_carts
            SET currency_id = (SELECT id FROM currencies WHERE currencies.code = shopping_carts.currency)
            WHERE currency IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('shopping_carts', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
