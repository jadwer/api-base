<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // is_public separates public catalog visibility from is_active.
            // Default true so all existing products stay in the public catalog.
            // Internal product = is_public false + is_active true (sellable and
            // quotable but hidden from the public storefront).
            $table->boolean('is_public')->default(true)->after('is_active');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn('is_public');
        });
    }
};
