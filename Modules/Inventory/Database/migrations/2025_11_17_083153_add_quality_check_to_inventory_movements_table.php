<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IV-009: Add quality check fields for warehouse exits and transfers
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->boolean('quality_checked')->default(false)->after('status');
            $table->timestamp('quality_checked_at')->nullable()->after('quality_checked');
            $table->unsignedBigInteger('quality_checked_by')->nullable()->after('quality_checked_at');
            $table->text('quality_check_notes')->nullable()->after('quality_checked_by');

            $table->foreign('quality_checked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['quality_checked_by']);
            $table->dropColumn([
                'quality_checked',
                'quality_checked_at',
                'quality_checked_by',
                'quality_check_notes'
            ]);
        });
    }
};
