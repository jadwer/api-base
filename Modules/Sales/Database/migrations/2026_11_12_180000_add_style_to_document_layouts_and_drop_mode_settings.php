<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diseno por plantilla y estilo por fila (atomo/documents 0.11.3, 2026-11-12):
 * - document_layouts.style: la plantilla de la galeria de la que parte el diseno.
 * - El color del documento ahora vive en el diseno (theme.mode). Los ajustes
 *   sueltos documents.builder.*_mode lo pisaban y se retiran.
 */
return new class extends Migration
{
    private const MODE_KEYS = ['documents.builder.quote_mode', 'documents.builder.remission_mode', 'documents.builder.sales_order_mode'];

    public function up(): void
    {
        if (! Schema::hasColumn('document_layouts', 'style')) {
            Schema::table('document_layouts', function (Blueprint $table) {
                $table->string('style', 40)->nullable()->after('document_type');
            });
        }
        DB::table('app_settings')->whereIn('key', self::MODE_KEYS)->delete();
        foreach (self::MODE_KEYS as $key) {
            Cache::forget("app_setting:{$key}");
        }
        Cache::forget('app_settings:grouped');
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_layouts', 'style')) {
            Schema::table('document_layouts', function (Blueprint $table) {
                $table->dropColumn('style');
            });
        }
    }
};
