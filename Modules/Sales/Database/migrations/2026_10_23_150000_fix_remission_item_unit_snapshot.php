<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * remission_items.unit guardaba el objeto Unit serializado (JSON) en vez del
 * nombre de la unidad: el controlador asignaba la relacion completa. Se deja
 * solo el nombre (23-10). Un registro afectado en prod al momento del arreglo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('remission_items')->where('unit', 'like', '{%')->orderBy('id')->each(function ($row) {
            $decoded = json_decode((string) $row->unit, true);
            DB::table('remission_items')->where('id', $row->id)->update([
                'unit' => is_array($decoded) ? ($decoded['name'] ?? $decoded['code'] ?? null) : null,
            ]);
        });
    }

    public function down(): void
    {
        // Sin reversa: el JSON anterior era un error de captura.
    }
};
