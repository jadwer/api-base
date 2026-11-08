<?php

namespace App\Support\Guide;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Historial de estados para la guia (atomo/guide) a partir del activity log de
 * Spatie, que ya registra `status` en cotizaciones, remisiones, cuentas por
 * cobrar, cobros y CFDI (`logOnly` + `logOnlyDirty`). Solo cuenta las filas
 * que traen `attributes.status` (con `logOnlyDirty` eso es un cambio de
 * estado); la fila `created` es el primer paso. Formato del contrato v1:
 * {from, to, at, by, reason}.
 */
final class ActivityStatusHistory
{
    /** @return list<array{from: ?string, to: string, at: ?string, by: ?string, reason: ?string}> */
    public static function for(Model $document): array
    {
        /** @var class-string<Model> $activityModel */
        $activityModel = config('activitylog.activity_model', \Spatie\Activitylog\Models\Activity::class);
        if (! class_exists($activityModel)) {
            return [];
        }

        $rows = $activityModel::query()
            ->where('subject_type', $document->getMorphClass())
            ->where('subject_id', $document->getKey())
            ->orderBy('id')
            ->get();

        $steps = [];
        foreach ($rows as $row) {
            $properties = $row->getAttribute('properties');
            $props = $properties instanceof Collection ? $properties->toArray() : (array) $properties;
            $attributes = $props['attributes'] ?? [];
            if (! array_key_exists('status', $attributes)) {
                continue;
            }
            $steps[] = [
                'from' => isset($props['old']['status']) ? (string) $props['old']['status'] : null,
                'to' => (string) $attributes['status'],
                'at' => $row->getAttribute('created_at')?->toIso8601String(),
                'by' => self::causerName($row),
                'reason' => isset($props['reason']) ? (string) $props['reason'] : null,
            ];
        }

        return $steps;
    }

    /**
     * Une dos fuentes (el `metadata.status_history` del pedido y su activity
     * log) sin repetir el mismo paso: mismo destino dentro del mismo minuto.
     *
     * @param  list<array{from: ?string, to: string, at: ?string, by: ?string, reason: ?string}>  ...$sources
     * @return list<array{from: ?string, to: string, at: ?string, by: ?string, reason: ?string}>
     */
    public static function merge(array ...$sources): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge(...$sources) as $step) {
            $minute = $step['at'] ? substr($step['at'], 0, 16) : '';
            $key = $step['to'].'|'.$minute;
            if (isset($seen[$key])) {
                // Si la fila repetida trae datos que la primera no tenia, se completan.
                $i = $seen[$key];
                $out[$i]['from'] ??= $step['from'];
                $out[$i]['by'] ??= $step['by'];
                $out[$i]['reason'] ??= $step['reason'];

                continue;
            }
            $seen[$key] = count($out);
            $out[] = $step;
        }
        usort($out, fn ($a, $b) => strcmp((string) $a['at'], (string) $b['at']));

        return $out;
    }

    /** Nombre de quien hizo el cambio a partir de un id de usuario o de la palabra "system". */
    public static function userName(int|string|null $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (! is_numeric($id)) {
            return $id === 'system' ? 'Sistema' : (string) $id;
        }

        return \App\Models\User::query()->whereKey($id)->value('name');
    }

    private static function causerName(object $row): ?string
    {
        if ($row->causer_id === null) {
            return 'Sistema';
        }

        return self::userName($row->causer_id) ?? null;
    }
}
