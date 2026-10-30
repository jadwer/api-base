<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Acentos en los textos sembrados que ya viven en la base (junta 2026-10-30):
 * nombres de series de factura, plantillas y correos del sistema, etiquetas
 * de configuracion. Los seeders ya traen los textos corregidos; esto alinea
 * las instalaciones existentes.
 *
 * Solo palabras completas y sin ambiguedad. En el HTML de las plantillas
 * solo se toca el texto entre etiquetas (nunca atributos ni URLs).
 */
return new class extends Migration
{
    private const WORDS = [
        'cotizacion' => 'cotización', 'confirmacion' => 'confirmación', 'devolucion' => 'devolución',
        'notificacion' => 'notificación', 'direccion' => 'dirección', 'cancelacion' => 'cancelación',
        'actualizacion' => 'actualización', 'verificacion' => 'verificación', 'atencion' => 'atención',
        'facturacion' => 'facturación', 'encriptacion' => 'encriptación', 'configuracion' => 'configuración',
        'seccion' => 'sección', 'revision' => 'revisión', 'continuacion' => 'continuación',
        'transaccion' => 'transacción', 'accion' => 'acción', 'relacion' => 'relación', 'emision' => 'emisión',
        'contabilizacion' => 'contabilización', 'recuperacion' => 'recuperación', 'comision' => 'comisión',
        'numero' => 'número', 'razon' => 'razón', 'telefono' => 'teléfono', 'envio' => 'envío',
        'contrasena' => 'contraseña', 'codigo' => 'código', 'automaticamente' => 'automáticamente',
        'automatico' => 'automático', 'titulo' => 'título', 'vacio' => 'vacío', 'metodo' => 'método',
        'ordenes' => 'órdenes', 'ultimos' => 'últimos', 'linea' => 'línea', 'catalogo' => 'catálogo',
        'credito' => 'crédito', 'dias' => 'días', 'almacen' => 'almacén', 'electronico' => 'electrónico',
    ];

    private const COLUMNS = [
        'invoice_series' => ['name', 'description'],
        'email_templates' => ['name', 'description', 'subject', 'html'],
        'system_emails' => ['name', 'description', 'default_subject'],
        'app_settings' => ['label', 'description'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')->each(function ($row) use ($table, $columns) {
                $changes = [];
                foreach ($columns as $column) {
                    $value = $row->{$column};
                    if (! is_string($value) || $value === '') {
                        continue;
                    }
                    $fixed = $column === 'html'
                        ? preg_replace_callback('/>([^<]+)</u', fn ($m) => '>'.$this->accent($m[1]).'<', $value)
                        : $this->accent($value);
                    if ($fixed !== $value) {
                        $changes[$column] = $fixed;
                    }
                }
                if ($changes) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            });
        }
    }

    public function down(): void
    {
        // Correccion ortografica: no se revierte.
    }

    private function accent(string $text): string
    {
        return preg_replace_callback('/\b([A-Za-z]+)\b/u', function ($m) {
            $word = $m[1];
            $fixed = self::WORDS[strtolower($word)] ?? null;
            if ($fixed === null) {
                return $word;
            }
            if (strtoupper($word) === $word && strlen($word) > 1) {
                return mb_strtoupper($fixed);
            }

            return ctype_upper($word[0]) ? mb_strtoupper(mb_substr($fixed, 0, 1)).mb_substr($fixed, 1) : $fixed;
        }, $text);
    }
};
