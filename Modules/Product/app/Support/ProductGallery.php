<?php

namespace Modules\Product\Support;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;

/**
 * Coherencia entre products.img_path y la galeria product_images (P1 granel).
 *
 * Regla: la fila primaria de la galeria y products.img_path apuntan al mismo
 * archivo. ProductImageObserver mantiene la direccion galeria -> img_path;
 * esta clase cubre la direccion inversa (import, bulk o PATCH que escriben
 * img_path) y el orden (la primaria queda con sort_order 0).
 */
final class ProductGallery
{
    /**
     * img_path del producto cambio: crear o ajustar la fila primaria.
     *
     * - img_path vacio: se elimina la fila primaria (el observer de imagenes
     *   promueve la siguiente o deja img_path en null).
     * - Ya coincide con la primaria: nada.
     * - Coincide con otra fila de la galeria: esa pasa a primaria.
     * - Hay primaria con otro archivo: se le cambia file_path (el observer
     *   borra el archivo anterior si quedo huerfano).
     * - No hay primaria: se crea con sort_order 0.
     */
    public static function syncFromProduct(Product $product): void
    {
        $new = trim((string) $product->img_path);
        $primary = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->first();

        if ($new === '') {
            $primary?->delete();

            return;
        }

        if ($primary && ProductImageFiles::same($primary->file_path, $new)) {
            return;
        }

        $match = ProductImage::query()
            ->where('product_id', $product->id)
            ->get()
            ->first(fn (ProductImage $image) => ProductImageFiles::same($image->file_path, $new));

        if ($match) {
            $match->update(['is_primary' => true]);

            return;
        }

        if ($primary) {
            $primary->update(['file_path' => $new]);

            return;
        }

        ProductImage::create([
            'product_id' => $product->id,
            'file_path' => $new,
            'sort_order' => 0,
            'is_primary' => true,
        ]);
    }

    /**
     * La primaria queda en sort_order 0 y las demas conservan su orden
     * relativo (1..n). Asi la galeria admin, la relacion Product::images() y la
     * ficha publica abren con la misma imagen. Query builder: sin eventos.
     */
    public static function renumberPrimaryFirst(int $productId): void
    {
        $images = ProductImage::query()
            ->where('product_id', $productId)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        foreach ($images->values() as $position => $image) {
            if ((int) $image->sort_order !== $position) {
                ProductImage::query()->whereKey($image->id)->update(['sort_order' => $position]);
            }
        }
    }
}
