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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217 code (USD, EUR, etc.)
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 12, 6)->default(1.000000); // Rate relative to base currency
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Only one default currency
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
