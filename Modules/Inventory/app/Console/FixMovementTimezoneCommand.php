<?php

namespace Modules\Inventory\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige la fecha de los movimientos capturados en pantalla antes del 2026-11-15.
 *
 * El alta mandaba la hora local del navegador sin zona y la aplicacion corre en UTC:
 * un movimiento registrado a las 21:43 de Mexico quedaba guardado como 21:43 UTC
 * (las 15:43 locales). El sistema (recepciones, entregas, cancelaciones, conteos)
 * registra con now() y no tiene el problema.
 *
 * Como se reconoce un movimiento capturado en pantalla:
 *   - el input datetime-local no manda segundos: la fecha termina en :00;
 *   - su fecha no coincide con la de creacion (los del sistema difieren segundos);
 *   - su origen es uno de los que ofrece el alta (manual, compra, venta, transferencia, ajuste).
 *
 * La correccion interpreta la fecha guardada como hora local de --tz y la pasa a UTC.
 * Cada fila corregida guarda en metadata.timezone_fix la fecha anterior; esa marca
 * impide aplicarla dos veces. Sin --apply solo lista lo que cambiaria.
 *
 * Uso:
 *   php artisan inventory:fix-movement-timezone                 # simulacion
 *   php artisan inventory:fix-movement-timezone --apply --until='2026-11-16 03:57'
 *   php artisan inventory:fix-movement-timezone --since=2026-07-16 --until=2026-11-16
 */
class FixMovementTimezoneCommand extends Command
{
    protected $signature = 'inventory:fix-movement-timezone
                            {--tz=America/Mexico_City : Zona horaria de quien capturaba}
                            {--since= : Solo movimientos creados desde esta fecha (Y-m-d)}
                            {--until= : Fecha y hora UTC en que el alta corregida llego a este entorno; obligatoria con --apply}
                            {--apply : Aplica la correccion; sin esta opcion solo simula}';

    protected $description = 'Corrige el corrimiento de zona horaria en la fecha de los movimientos capturados en pantalla';

    /** Origenes que ofrece el alta de movimientos (InventoryMovementRequest). */
    private const FORM_REFERENCE_TYPES = ['manual', 'purchase', 'sale', 'transfer', 'adjustment'];

    /** Diferencia minima, en segundos, entre fecha y creacion para no tocar los del sistema. */
    private const SYSTEM_TOLERANCE_SECONDS = 120;

    public function handle(): int
    {
        $tz = (string) $this->option('tz');
        if (! in_array($tz, timezone_identifiers_list(), true)) {
            $this->error("Zona horaria desconocida: {$tz}");

            return self::FAILURE;
        }
        $apply = (bool) $this->option('apply');
        // Despues del arreglo los movimientos de pantalla tambien terminan en :00 y ya estan bien:
        // sin el corte se moverian seis horas los correctos.
        if ($apply && ! $this->option('until')) {
            $this->error('Indica --until con la fecha y hora (UTC) del despliegue que corrigio el alta.');

            return self::FAILURE;
        }
        $until = $this->option('until') ? Carbon::parse($this->option('until'), 'UTC') : Carbon::now('UTC');

        $query = DB::table('inventory_movements')
            ->whereIn('reference_type', self::FORM_REFERENCE_TYPES)
            ->where('created_at', '<', $until)
            ->orderBy('id');
        if ($this->option('since')) {
            $query->where('created_at', '>=', Carbon::parse($this->option('since'), 'UTC'));
        }

        $rows = [];
        foreach ($query->get(['id', 'product_id', 'warehouse_id', 'movement_type', 'movement_date', 'created_at', 'metadata']) as $m) {
            $metadata = json_decode((string) $m->metadata, true) ?: [];
            if (isset($metadata['timezone_fix'])) {
                continue;
            }
            $stored = Carbon::parse($m->movement_date, 'UTC');
            if ($stored->second !== 0) {
                continue;
            }
            if (abs($stored->diffInSeconds(Carbon::parse($m->created_at, 'UTC'), false)) <= self::SYSTEM_TOLERANCE_SECONDS) {
                continue;
            }
            $fixed = Carbon::parse($stored->format('Y-m-d H:i:s'), $tz)->utc();
            $rows[] = [$m, $metadata, $stored, $fixed];
        }

        if ($rows === []) {
            $this->info('No hay movimientos por corregir.');

            return self::SUCCESS;
        }

        $this->table(
            ['Id', 'Tipo', 'Guardada (UTC)', 'Corregida (UTC)', 'Creado (UTC)'],
            array_map(fn ($r) => [$r[0]->id, $r[0]->movement_type, $r[2]->format('Y-m-d H:i'), $r[3]->format('Y-m-d H:i'), $r[0]->created_at], $rows),
        );

        if (! $apply) {
            $this->warn(count($rows).' movimientos por corregir. Simulacion: no se cambio nada; usa --apply para aplicarla.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as [$m, $metadata, $stored, $fixed]) {
                $metadata['timezone_fix'] = [
                    'from' => $stored->format('Y-m-d H:i:s'),
                    'to' => $fixed->format('Y-m-d H:i:s'),
                    'at' => now('UTC')->format('Y-m-d H:i:s'),
                ];
                DB::table('inventory_movements')->where('id', $m->id)->update([
                    'movement_date' => $fixed->format('Y-m-d H:i:s'),
                    'metadata' => json_encode($metadata),
                ]);
                // stocks.last_movement_date es solo fecha y ya guardaba el dia local correcto: no se toca.
            }
        });

        $this->info(count($rows).' movimientos corregidos. La fecha anterior queda en metadata.timezone_fix.');

        return self::SUCCESS;
    }
}
