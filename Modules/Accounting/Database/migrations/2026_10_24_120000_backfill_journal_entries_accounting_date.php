<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las polizas nacian sin accounting_date y los estados financieros (que
 * filtran por ella) salian en cero. Se toma la fecha de la poliza; el
 * modelo ya la llena al guardar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('journal_entries')
            ->whereNull('accounting_date')
            ->whereNotNull('date')
            ->update(['accounting_date' => DB::raw('date')]);
    }

    public function down(): void
    {
        // Sin reversa: accounting_date = date es el valor correcto.
    }
};
