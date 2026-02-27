<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('brand_id')
                ->constrained('currencies')->nullOnDelete();
        });

        // Set default currency for existing products
        $defaultCurrency = DB::table('currencies')->where('is_default', true)->first();
        if ($defaultCurrency) {
            DB::table('products')->whereNull('currency_id')->update([
                'currency_id' => $defaultCurrency->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
