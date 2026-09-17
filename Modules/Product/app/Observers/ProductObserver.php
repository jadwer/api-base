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
        if (is_string($product->slug) && trim($product->slug) !== '') {
            return;
        }

        $base = ProductSlug::forProduct($product);
        $product->slug = ProductSlug::unique($base, $product->exists ? (int) $product->id : null);
    }
}
