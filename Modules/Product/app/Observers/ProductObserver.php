<?php

namespace Modules\Product\Observers;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\StorefrontRevalidator;
use Modules\Product\Support\ProductGallery;
use Modules\Product\Support\ProductImageFiles;
use Modules\Product\Support\ProductSlug;

/**
 * Genera el slug al crear (SEO Bloque 1b). Tambien lo rellena si un producto
 * previo al backfill llega a guardarse sin slug. Nunca lo reescribe cuando ya
 * existe: las URLs indexadas deben ser estables.
 */
class ProductObserver
{
    public function saving(Product $product): void
    {
        // P2 granel: un producto sujeto a cotizacion no tiene precio de lista
        // ni oferta, venga de la API, de un import o de un bulk Eloquent.
        $product->normalizePricing();

        $this->forgetReplacedDatasheet($product);

        if (is_string($product->slug) && trim($product->slug) !== '') {
            return;
        }

        $base = ProductSlug::forProduct($product);
        $product->slug = ProductSlug::unique($base, $product->exists ? (int) $product->id : null);
    }

    /**
     * Imagen principal (P1 granel, 2026-10-10): si img_path cambio por fuera de
     * la galeria (import, bulk, PATCH imgPath), la fila primaria de
     * product_images se crea o ajusta para que la galeria del formulario, la
     * tarjeta y la ficha publica muestren lo mismo. Antes 24.5 % de los
     * productos tenian img_path sin fila y la galeria salia vacia.
     */
    public function created(Product $product): void
    {
        if (trim((string) $product->img_path) !== '') {
            ProductGallery::syncFromProduct($product);
        }
        app(StorefrontRevalidator::class)->product($product);
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged('img_path')) {
            $old = $product->getOriginal('img_path');
            ProductGallery::syncFromProduct($product);
            if (is_string($old) && $old !== '' && ! ProductImageFiles::same($old, (string) $product->img_path)) {
                ProductImageFiles::deleteIfOrphan($old);
            }
        }

        if ($this->hasStorefrontChanges($product)) {
            app(StorefrontRevalidator::class)->product($product);
        }
    }

    /**
     * Rutas de imagen del producto que se esta borrando. Estatico: Laravel
     * resuelve una instancia nueva del observer por evento.
     *
     * @var array<int, list<string>>
     */
    private static array $pendingImageFiles = [];

    /**
     * Al borrar el producto la FK en cascada elimina sus filas de galeria sin
     * eventos: se guardan las rutas antes y se limpian los huerfanos despues.
     */
    public function deleting(Product $product): void
    {
        $paths = ProductImage::query()->where('product_id', $product->id)->pluck('file_path')->all();
        $paths[] = (string) $product->img_path;
        self::$pendingImageFiles[(int) $product->id] = array_values(array_filter($paths));
    }

    public function deleted(Product $product): void
    {
        $key = (int) $product->id;
        $paths = self::$pendingImageFiles[$key] ?? [];
        unset(self::$pendingImageFiles[$key]);

        // Las filas de galeria se borran por FK en cascada (sin eventos).
        ProductImage::query()->where('product_id', $product->id)->delete();
        foreach (array_unique($paths) as $path) {
            ProductImageFiles::deleteIfOrphan($path);
        }

        app(StorefrontRevalidator::class)->product($product);
    }

    /** Cambios que no se ven en la tienda (contadores) no revalidan. */
    private function hasStorefrontChanges(Product $product): bool
    {
        $ignored = ['updated_at', 'total_sales', 'average_rating', 'total_reviews'];

        return array_diff(array_keys($product->getChanges()), $ignored) !== [];
    }

    /**
     * Ficha tecnica (2026-10-06): al reemplazarla o quitarla, el PDF anterior
     * se borra del disco si ningun otro producto lo usa. Antes cada reemplazo
     * dejaba el archivo viejo huerfano en storage/datasheets.
     */
    private function forgetReplacedDatasheet(Product $product): void
    {
        if (! $product->exists || ! $product->isDirty('datasheet_path')) {
            return;
        }

        $old = (string) $product->getOriginal('datasheet_path');
        if ($old === '' || $old === (string) $product->datasheet_path) {
            return;
        }

        $path = str_starts_with($old, 'datasheets/') ? $old : 'datasheets/' . basename($old);
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($path)) {
            return;
        }

        $stillUsed = Product::where('id', '<>', $product->id)
            ->whereIn('datasheet_path', [$old, $path, basename($old)])
            ->exists();

        if (! $stillUsed && config('products.delete_orphan_files', true)) {
            // Despues del commit: si el guardado se revierte, el PDF se queda.
            \Illuminate\Support\Facades\DB::afterCommit(static fn () => $disk->delete($path));
        }
    }
}
