<?php

namespace Modules\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;

/**
 * Fichas tecnicas que apuntan a un archivo inexistente (2026-10-06).
 *
 * El catalogo importado trae datasheet_path como "HA-10018.pdf" para miles de
 * productos cuyo PDF nunca llego al servidor: el boton "Ficha tecnica" aparece
 * y da 404 (en prod, 21,430 de 36,643). Este comando lista o limpia esas
 * referencias; la lista se guarda en CSV para poder restaurarlas.
 */
class PruneMissingDatasheets extends Command
{
    protected $signature = 'products:prune-missing-datasheets
                            {--apply : Pone en NULL las referencias rotas (sin esta opcion solo reporta)}
                            {--csv= : Ruta del CSV de respaldo (id, sku, datasheet_path)}';

    protected $description = 'Reporta o limpia datasheet_path de productos cuyo PDF no existe en storage';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $existing = array_flip($disk->files('datasheets'));
        $missing = [];
        $total = 0;

        Product::query()
            ->whereNotNull('datasheet_path')
            ->where('datasheet_path', '<>', '')
            ->select(['id', 'sku', 'datasheet_path'])
            ->orderBy('id')
            ->chunk(1000, function ($products) use (&$missing, &$total, $existing) {
                foreach ($products as $product) {
                    $total++;
                    $path = $product->datasheet_path;
                    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                        continue;
                    }
                    $norm = str_starts_with($path, 'datasheets/') ? $path : 'datasheets/' . basename($path);
                    if (! isset($existing[$norm])) {
                        $missing[] = [$product->id, $product->sku, $path];
                    }
                }
            });

        $this->info(sprintf('Productos con ficha registrada: %d. Con archivo faltante: %d.', $total, count($missing)));

        if ($csv = $this->option('csv')) {
            $fh = fopen($csv, 'w');
            fputcsv($fh, ['id', 'sku', 'datasheet_path']);
            foreach ($missing as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
            $this->line("Respaldo: {$csv}");
        }

        if (! $this->option('apply')) {
            $this->line('Solo reporte. Usa --apply para limpiar (recomendado con --csv).');

            return self::SUCCESS;
        }

        $ids = array_column($missing, 0);
        $updated = 0;
        foreach (array_chunk($ids, 1000) as $chunk) {
            // Query builder: no dispara observers ni toca updated_at.
            $updated += Product::query()->whereIn('id', $chunk)->update(['datasheet_path' => null]);
        }
        $this->info("Referencias limpiadas: {$updated}.");

        return self::SUCCESS;
    }
}
