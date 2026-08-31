<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extension telefonica del contacto (feedback cliente 2026-07-30):
 * el conmutador de muchos clientes requiere "tel + ext" y phone es max:20.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('phone_extension', 10)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('phone_extension');
        });
    }
};
