<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add delivered_at and printed_at timestamps to remissions table.
     */
    public function up(): void
    {
        Schema::table('remissions', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('delivery_date');
            $table->timestamp('printed_at')->nullable()->after('pdf_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('remissions', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'printed_at']);
        });
    }
};
