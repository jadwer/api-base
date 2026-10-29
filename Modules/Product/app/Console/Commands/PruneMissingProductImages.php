<?php

namespace Modules\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\StorefrontRevalidator;
use Modules\Product\Support\ProductGallery;
use Modules\Product\Support\ProductImageFiles;

/**
 * Referencias a imagenes que no existen en el disco (2026-10-29).
 *
 * En prod 1,795 productos publicos apuntaban a un archivo inexistente: 1,042
 * con img_path ".webp" (nombre vacio desde el import) y 753 con un nombre que
 * nunca se subio. products:sync-images (12-10) los copio como primaria de la
 * galeria y la ficha publica pintaba la imagen rota. Este comando reporta y,
 * con --apply:
 *
 * - borra de product_images las filas cuyo archivo local no existe;
 * - si quedan imagenes, la primera pasa a primaria y se copia a img_path;
 * - si no queda ninguna, img_path queda en null (la ficha dice "Sin imagen").
 *
 * Solo revisa archivos locales (products/ en el disco public); URLs externas y
 * rutas de public/ ("/images/") no se tocan. Query builder: sin observers, no
 * borra archivos. El CSV guarda lo previo para poder revertir.
 */
class PruneMissingProductImages extends Command
{
    protected $signature = 'products:prune-missing-images
                            {--apply : Aplica las correcciones (sin esta opcion solo reporta)}
                            {--csv= : Ruta del CSV de respaldo (product_id, sku, img_path, image_id, file_path, sort_order, is_primary)}';

    protected $description = 'Reporta o quita las imagenes de producto cuyo archivo no existe en el disco';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $disk = Storage::disk('public');
        /** @var array<string, bool> $exists */
        $exists = [];
        $missing = function (?string $stored) use ($disk, &$exists): bool {
            $path = ProductImageFiles::diskPath($stored);
            if ($path === null) {
                return false;
            }

            return ! ($exists[$path] ??= $disk->exists($path));
        };

        /** @var array<int, list<ProductImage>> $badRows */
        $badRows = [];
        ProductImage::query()
            ->select(['id', 'product_id', 'file_path', 'sort_order', 'is_primary'])
            ->orderBy('id')
            ->chunkById(2000, function ($images) use (&$badRows, $missing) {
                foreach ($images as $image) {
                    if ($missing($image->file_path)) {
                        $badRows[(int) $image->product_id][] = $image;
                    }
                }
            });

        /** @var array<int, true> $badImgPath */
        $badImgPath = [];
        Product::query()
            ->whereNotNull('img_path')
            ->where('img_path', '<>', '')
            ->select(['id', 'img_path'])
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (&$badImgPath, $missing) {
                foreach ($products as $product) {
                    if ($missing($product->img_path)) {
                        $badImgPath[(int) $product->id] = true;
                    }
                }
            });

        $productIds = array_values(array_unique([...array_keys($badRows), ...array_keys($badImgPath)]));
        sort($productIds);
        $rowCount = array_sum(array_map('count', $badRows));

        $this->info(sprintf('Productos con imagen inexistente: %d. Filas de galeria a quitar: %d.', count($productIds), $rowCount));

        $products = Product::query()->whereIn('id', $productIds)->get(['id', 'sku', 'img_path'])->keyBy('id');

        if ($csv = $this->option('csv')) {
            $fh = fopen($csv, 'w');
            if ($fh === false) {
                $this->error("No se pudo escribir {$csv}");

                return self::FAILURE;
            }
            fputcsv($fh, ['product_id', 'sku', 'img_path', 'image_id', 'file_path', 'sort_order', 'is_primary']);
            foreach ($productIds as $id) {
                $product = $products->get($id);
                foreach ($badRows[$id] ?? [null] as $image) {
                    fputcsv($fh, [$id, $product?->sku, $product?->img_path, $image?->id, $image?->file_path, $image?->sort_order, $image ? (int) $image->is_primary : '']);
                }
            }
            fclose($fh);
            $this->line("Respaldo: {$csv}");
        }

        if (! $apply) {
            $this->line('Solo reporte. Usa --apply para corregir (recomendado con --csv).');

            return self::SUCCESS;
        }

        $withImage = 0;
        foreach (array_chunk($productIds, 500) as $chunk) {
            DB::transaction(function () use ($chunk, $badRows, $missing, &$withImage) {
                foreach ($chunk as $id) {
                    $ids = array_map(fn (ProductImage $image) => $image->id, $badRows[$id] ?? []);
                    if ($ids !== []) {
                        ProductImage::query()->whereIn('id', $ids)->delete();
                    }

                    $first = ProductImage::query()->where('product_id', $id)
                        ->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')
                        ->get(['id', 'file_path'])
                        ->first(fn (ProductImage $image) => ! $missing($image->file_path));

                    if ($first) {
                        $withImage++;
                        ProductImage::query()->where('product_id', $id)->where('id', '<>', $first->id)->update(['is_primary' => false]);
                        ProductImage::query()->whereKey($first->id)->update(['is_primary' => true]);
                        ProductGallery::renumberPrimaryFirst($id);
                    }
                    Product::query()->whereKey($id)->update(['img_path' => $first?->file_path]);
                }
            });
        }

        $this->info(sprintf('Correcciones aplicadas: %d productos (%d con otra imagen como principal, %d sin imagen).', count($productIds), $withImage, count($productIds) - $withImage));
        if ($productIds !== []) {
            app(StorefrontRevalidator::class)->everything();
        }

        return self::SUCCESS;
    }
}
