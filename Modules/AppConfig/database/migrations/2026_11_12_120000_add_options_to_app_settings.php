<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AppConfig\Support\DocumentSettingDefinitions;

/**
 * Opciones de un ajuste de lista cerrada, servidas por el backend (regla 7: el
 * panel no guarda copia de los valores validos). Alinea las instalaciones
 * existentes: etiqueta, descripcion y opciones de los ajustes del constructor
 * de documentos y del origen del emisor; el valor guardado no se toca, y lo
 * que falta se crea con su valor por omision (constructor apagado).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_settings', 'options')) {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->json('options')->nullable()->after('description');
            });
        }

        $now = now();
        foreach (DocumentSettingDefinitions::all() as $def) {
            $meta = [
                'type' => $def['type'],
                'group' => $def['group'],
                'label' => $def['label'],
                'description' => $def['description'],
                'options' => $def['options'] !== null ? json_encode($def['options'], JSON_UNESCAPED_UNICODE) : null,
                'updated_at' => $now,
            ];
            if (DB::table('app_settings')->where('key', $def['key'])->exists()) {
                DB::table('app_settings')->where('key', $def['key'])->update($meta);
            } else {
                DB::table('app_settings')->insert($meta + ['key' => $def['key'], 'value' => $def['value'], 'created_at' => $now]);
            }
        }

        Cache::forget('app_settings:grouped');
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_settings', 'options')) {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->dropColumn('options');
            });
        }
    }
};
