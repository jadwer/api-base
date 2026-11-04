<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Support\GlAccounts;

/**
 * Siembra las cuentas que piden las reglas de poliza corregidas (auditoria
 * finanzas 2026-10, puntos 1 y 2): IVA acreditable pendiente de pago (1110),
 * IVA trasladado no cobrado (2109) y mercancia recibida por facturar (2110).
 *
 * Solo en catalogos que ya tienen la cuenta padre (1100, 2100); en una base
 * vacia las crea GlAccounts::get() o el seeder del catalogo. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        foreach (GlAccounts::SYSTEM as $code => [$name, $type, $parentCode]) {
            $parent = DB::table('accounts')->where('code', $parentCode)->first();
            if (!$parent || DB::table('accounts')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('accounts')->insert([
                'company_id' => $parent->company_id,
                'code' => $code,
                'name' => $name,
                'account_type' => $type,
                'level' => (int) $parent->level + 1,
                'parent_id' => $parent->id,
                'currency' => 'MXN',
                'is_postable' => true,
                'is_cash_flow' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Solo se quitan si nadie las uso.
        foreach (array_keys(GlAccounts::SYSTEM) as $code) {
            $id = DB::table('accounts')->where('code', $code)->value('id');
            if ($id && !DB::table('journal_lines')->where('account_id', $id)->exists()) {
                DB::table('accounts')->where('id', $id)->delete();
            }
        }
    }
};
