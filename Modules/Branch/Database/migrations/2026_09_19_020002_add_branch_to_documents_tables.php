<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sucursal de origen en los documentos del ciclo. Es un atributo para
 * filtrar listados y etiquetar el PDF/factura ("Matriz", "Suc. Toluca"):
 * la entidad fiscal es una sola (decision Jasim 18-sep-2026), asi que NO
 * cambia CFDI ni lugar de expedicion. Backfill: historico a la Matriz.
 */
return new class extends Migration
{
    private array $tables = [
        'quotes',
        'sales_orders',
        'purchase_orders',
        'remissions',
        'cfdi_invoices',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name) || Schema::hasColumn($name, 'branch_id')) {
                continue;
            }

            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->constrained('branches')
                    ->onDelete('restrict');
            });
        }

        $mainId = DB::table('branches')->where('is_main', true)->value('id');
        if ($mainId) {
            foreach ($this->tables as $name) {
                if (Schema::hasTable($name)) {
                    DB::table($name)->whereNull('branch_id')->update(['branch_id' => $mainId]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'branch_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('branch_id');
                });
            }
        }
    }
};
