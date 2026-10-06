<?php

namespace Modules\Product\Observers;

use Modules\Product\Models\Product;
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
        $this->forgetReplacedDatasheet($product);


        if (is_string($product->slug) && trim($product->slug) !== '') {
            return;
        }

        $base = ProductSlug::forProduct($product);
        $product->slug = ProductSlug::unique($base, $product->exists ? (int) $product->id : null);
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

        if (! $stillUsed) {
            $disk->delete($path);
        }
    }
}
