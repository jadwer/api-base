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
            $table->boolean('is_active')->default(true)->after('brand_id');
            $table->float('average_rating')->nullable()->after('is_active');
            $table->integer('total_reviews')->default(0)->after('average_rating');
            $table->integer('total_sales')->default(0)->after('total_reviews');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'average_rating', 'total_reviews', 'total_sales']);
        });
    }
};
