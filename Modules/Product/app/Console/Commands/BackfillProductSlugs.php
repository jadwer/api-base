<?php

namespace Modules\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Product\Support\ProductSlug;

/**
 * Rellena products.slug donde falta (SEO Bloque 1b, 2026-09).
 *
 * Idempotente: solo toca filas con slug NULL o vacio, en lotes, con
 * escritura directa (sin observers ni updated_at, para no mover el lastmod
 * del sitemap de 39k productos). Seguro de repetir y de interrumpir.
 *
 * Uso:
 *   php artisan products:backfill-slugs            (escribe)
 *   php artisan products:backfill-slugs --dry-run  (solo cuenta)
 *   php artisan products:backfill-slugs --chunk=1000
 */
class BackfillProductSlugs extends Command
{
    protected $signature = 'products:backfill-slugs
                            {--chunk=500 : Filas por lote}
                            {--dry-run : No escribe, solo reporta cuantas faltan}';

    protected $description = 'Genera el slug de los productos que no lo tienen (idempotente, por lotes)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $pending = $this->pendingQuery()->count();
        $this->info("Productos sin slug: {$pending}");

        if ($dryRun || $pending === 0) {
            return self::SUCCESS;
        }

        $done = 0;
        $taken = [];

        // orderBy id + lastId evita saltarse filas conforme dejan de cumplir el where
        $lastId = 0;
        while (true) {
            $rows = $this->pendingQuery()
                ->where('products.id', '>', $lastId)
                ->orderBy('products.id')
                ->limit($chunk)
                ->get(['products.id', 'products.name', 'products.sku', 'brands.name as brand_name']);

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $base = ProductSlug::build($row->name, $row->brand_name, $row->sku);
                $slug = ProductSlug::unique($base, (int) $row->id, $taken);
                DB::table('products')->where('id', $row->id)->update(['slug' => $slug]);
                $done++;
                $lastId = (int) $row->id;
            }

            $this->line("  {$done}/{$pending}");
        }

        $this->info("Slugs generados: {$done}");

        return self::SUCCESS;
    }

    private function pendingQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('products')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where(function ($q) {
                $q->whereNull('products.slug')->orWhere('products.slug', '');
            });
    }
}
