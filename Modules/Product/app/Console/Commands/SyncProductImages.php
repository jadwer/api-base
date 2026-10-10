<?php

namespace Modules\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\StorefrontRevalidator;
use Modules\Product\Support\ProductGallery;
use Modules\Product\Support\ProductImageFiles;

/**
 * Galeria de imagenes desalineada con products.img_path (P1 granel, 2026-10-10).
 *
 * En la copia local de dev 9,581 de 39,042 productos con img_path no tenian ninguna fila en
 * product_images (la galeria del formulario salia vacia y la imagen no se podia
 * quitar ni cambiar) y 5 tenian una primaria con otro archivo. Este comando
 * reporta y, con --apply, deja la fila primaria igual a img_path:
 *
 * - sin filas: crea la primaria (sort_order 0) con img_path;
 * - primaria con otro archivo: si otra fila ya tiene img_path la hace
 *   primaria; si no, agrega img_path como primaria y la anterior queda como
 *   imagen secundaria (no se pierde nada);
 * - filas sin primaria: igual que el caso anterior;
 * - galeria sin img_path: copia a img_path la primaria (o la primera fila).
 *
 * Solo escribe en la base (query builder: sin observers, no borra archivos).
 * El CSV guarda lo previo para poder revertir.
 */
class SyncProductImages extends Command
{
    protected $signature = 'products:sync-images
                            {--apply : Aplica las correcciones (sin esta opcion solo reporta)}
                            {--csv= : Ruta del CSV de respaldo (product_id, sku, img_path, accion, image_id, file_path previo)}';

    protected $description = 'Reporta o corrige productos cuya galeria (product_images) no coincide con img_path';

    public const ACTION_CREATE = 'crear_primaria';

    public const ACTION_PROMOTE = 'promover_existente';

    public const ACTION_ADD = 'agregar_primaria';

    public const ACTION_SET_IMG_PATH = 'copiar_a_img_path';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $rows = [];
        $counts = array_fill_keys([self::ACTION_CREATE, self::ACTION_PROMOTE, self::ACTION_ADD, self::ACTION_SET_IMG_PATH], 0);
        $withImage = 0;

        Product::query()
            ->whereNotNull('img_path')
            ->where('img_path', '<>', '')
            ->select(['id', 'sku', 'img_path'])
            ->orderBy('id')
            ->chunkById(1000, function (Collection $products) use (&$rows, &$counts, &$withImage, $apply) {
                $gallery = ProductImage::query()
                    ->whereIn('product_id', $products->pluck('id'))
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'product_id', 'file_path', 'is_primary', 'sort_order'])
                    ->groupBy('product_id');

                foreach ($products as $product) {
                    $withImage++;
                    /** @var Collection<int, ProductImage> $images */
                    $images = $gallery->get($product->id, collect());
                    [$action, $target] = $this->classify($product, $images);
                    if ($action === null) {
                        continue;
                    }
                    $counts[$action]++;
                    $primary = $images->firstWhere('is_primary', true);
                    $rows[] = [$product->id, $product->sku, $product->img_path, $action, $primary->id ?? '', $primary->file_path ?? ''];
                    if ($apply) {
                        $this->fix($product, $action, $target);
                    }
                }
            });

        // Galeria sin img_path (al reves): la tarjeta y la ficha salen sin imagen.
        Product::query()
            ->where(fn ($q) => $q->whereNull('img_path')->orWhere('img_path', ''))
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('product_images')->whereColumn('product_images.product_id', 'products.id'))
            ->select(['id', 'sku', 'img_path'])
            ->orderBy('id')
            ->chunkById(1000, function (Collection $products) use (&$rows, &$counts, $apply) {
                foreach ($products as $product) {
                    $counts[self::ACTION_SET_IMG_PATH]++;
                    $first = ProductImage::query()->where('product_id', $product->id)
                        ->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->first();
                    $rows[] = [$product->id, $product->sku, '', self::ACTION_SET_IMG_PATH, $first->id ?? '', $first->file_path ?? ''];
                    if ($apply && $first) {
                        DB::transaction(function () use ($product, $first) {
                            ProductImage::query()->where('product_id', $product->id)->where('id', '<>', $first->id)->update(['is_primary' => false]);
                            ProductImage::query()->whereKey($first->id)->update(['is_primary' => true]);
                            ProductGallery::renumberPrimaryFirst((int) $product->id);
                            Product::query()->whereKey($product->id)->update(['img_path' => $first->file_path]);
                        });
                    }
                }
            });

        $this->info(sprintf('Productos con img_path: %d. Por corregir: %d.', $withImage, array_sum($counts)));
        $this->table(['Accion', 'Productos'], collect($counts)->map(fn ($n, $a) => [$a, $n])->values()->all());

        if ($csv = $this->option('csv')) {
            $fh = fopen($csv, 'w');
            if ($fh === false) {
                $this->error("No se pudo escribir {$csv}");

                return self::FAILURE;
            }
            fputcsv($fh, ['product_id', 'sku', 'img_path', 'accion', 'image_id_primaria_previa', 'file_path_primaria_previa']);
            foreach ($rows as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
            $this->line("Respaldo: {$csv}");
        }

        if (! $apply) {
            $this->line('Solo reporte. Usa --apply para corregir (recomendado con --csv).');

            return self::SUCCESS;
        }

        $this->info('Correcciones aplicadas: ' . array_sum($counts) . '.');
        if (array_sum($counts) > 0) {
            app(StorefrontRevalidator::class)->everything();
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return array{0: string|null, 1: ProductImage|null}
     */
    private function classify(Product $product, Collection $images): array
    {
        if ($images->isEmpty()) {
            return [self::ACTION_CREATE, null];
        }

        $primary = $images->firstWhere('is_primary', true);
        if ($primary && ProductImageFiles::same($primary->file_path, $product->img_path)) {
            return [null, null];
        }

        $match = $images->first(fn (ProductImage $image) => ProductImageFiles::same($image->file_path, $product->img_path));

        return $match ? [self::ACTION_PROMOTE, $match] : [self::ACTION_ADD, null];
    }

    private function fix(Product $product, string $action, ?ProductImage $target): void
    {
        DB::transaction(function () use ($product, $action, $target) {
            $now = now();
            if ($action === self::ACTION_PROMOTE && $target) {
                ProductImage::query()->where('product_id', $product->id)->where('id', '<>', $target->id)->update(['is_primary' => false]);
                ProductImage::query()->whereKey($target->id)->update(['is_primary' => true, 'updated_at' => $now]);
            } else {
                ProductImage::query()->where('product_id', $product->id)->update(['is_primary' => false]);
                ProductImage::query()->insert([
                    'product_id' => $product->id,
                    'file_path' => $product->img_path,
                    'alt_text' => null,
                    'sort_order' => 0,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            if ($action !== self::ACTION_CREATE) {
                ProductGallery::renumberPrimaryFirst((int) $product->id);
            }
        });
    }
}
