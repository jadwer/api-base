<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Usuario con sucursal principal (users.branch_id) y sucursales con acceso
 * (pivot branch_user), igual que la pantalla de alta de Bind ERP.
 * Backfill: todos los usuarios existentes quedan en la Matriz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->onDelete('restrict');
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['branch_id', 'user_id']);
        });

        $mainId = DB::table('branches')->where('is_main', true)->value('id');
        if ($mainId) {
            DB::table('users')->whereNull('branch_id')->update(['branch_id' => $mainId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
