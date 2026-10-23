<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cancelacion de fraccionamiento (G10 de granel, 23-10): quien, cuando y por que. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fractionations', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('executed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 500)->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('fractionations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
