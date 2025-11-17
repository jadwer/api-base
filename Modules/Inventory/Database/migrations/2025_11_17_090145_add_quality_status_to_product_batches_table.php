<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IV-009: Add quality status field to product batches
     */
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->enum('quality_status', [
                'pending',
                'in_testing',
                'passed',
                'failed',
                'quarantine'
            ])->default('pending')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('quality_status');
        });
    }
};
