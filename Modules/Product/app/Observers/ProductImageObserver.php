<?php

namespace Modules\Product\Observers;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\StorefrontRevalidator;
use Modules\Product\Support\ProductGallery;
use Modules\Product\Support\ProductImageFiles;

/**
 * Mantiene products.img_path = file_path de la imagen primaria.
 *
 * Todas las escrituras sobre products van por query builder (sin eventos), asi
 * que ProductObserver no vuelve a entrar y no hay bucle con
 * ProductGallery::syncFromProduct.
 */
class ProductImageObserver
{
    /**
     * Primera imagen del producto: queda primaria. Si llega marcada como
     * primaria, apaga las demas y se mueve al frente.
     */
    public function created(ProductImage $productImage): void
    {
        $count = ProductImage::where('product_id', $productImage->product_id)->count();

        if ($count === 1) {
            $productImage->updateQuietly(['is_primary' => true, 'sort_order' => 0]);
            $this->syncProductImgPath($productImage);
        } elseif ($productImage->is_primary) {
            $this->unsetOtherPrimaries($productImage);
            ProductGallery::renumberPrimaryFirst((int) $productImage->product_id);
            $this->syncProductImgPath($productImage);
        }

        $this->revalidate($productImage);
    }

    /**
     * Cambio a primaria: apaga las demas, la mueve al frente y sincroniza.
     * Cambio de archivo (Reemplazar): si es la primaria sincroniza img_path; el
     * archivo anterior se borra si quedo huerfano.
     */
    public function updated(ProductImage $productImage): void
    {
        $becamePrimary = $productImage->wasChanged('is_primary') && $productImage->is_primary;
        $fileChanged = $productImage->wasChanged('file_path');

        if ($becamePrimary) {
            $this->unsetOtherPrimaries($productImage);
            ProductGallery::renumberPrimaryFirst((int) $productImage->product_id);
        }

        if ($productImage->is_primary && ($becamePrimary || $fileChanged)) {
            $this->syncProductImgPath($productImage);
        }

        if ($fileChanged) {
            ProductImageFiles::deleteIfOrphan((string) $productImage->getOriginal('file_path'));
        }

        if ($productImage->wasChanged(['is_primary', 'file_path', 'alt_text', 'sort_order'])) {
            $this->revalidate($productImage);
        }
    }

    /**
     * Si era la primaria promueve la siguiente (por sort_order) o deja img_path
     * en null. El archivo se borra si ya nadie lo usa.
     */
    public function deleted(ProductImage $productImage): void
    {
        if ($productImage->is_primary) {
            $next = ProductImage::where('product_id', $productImage->product_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($next) {
                $next->updateQuietly(['is_primary' => true]);
                ProductGallery::renumberPrimaryFirst((int) $productImage->product_id);
                $this->syncProductImgPath($next);
            } else {
                Product::query()->whereKey($productImage->product_id)->update(['img_path' => null]);
            }
        }

        ProductImageFiles::deleteIfOrphan($productImage->file_path);
        $this->revalidate($productImage);
    }

    /**
     * Unset is_primary on all other images for the same product.
     */
    private function unsetOtherPrimaries(ProductImage $productImage): void
    {
        ProductImage::where('product_id', $productImage->product_id)
            ->where('id', '!=', $productImage->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    /**
     * Copia file_path de la primaria a products.img_path. Si la imagen que
     * estaba en img_path no esta en la galeria (dato legado), su archivo se
     * borra al quedar huerfano.
     */
    private function syncProductImgPath(ProductImage $productImage): void
    {
        $previous = Product::query()->whereKey($productImage->product_id)->value('img_path');
        if ($previous === $productImage->file_path) {
            return;
        }

        Product::query()->whereKey($productImage->product_id)->update(['img_path' => $productImage->file_path]);

        if (is_string($previous) && $previous !== '' && ! ProductImageFiles::same($previous, $productImage->file_path)) {
            ProductImageFiles::deleteIfOrphan($previous);
        }
    }

    private function revalidate(ProductImage $productImage): void
    {
        app(StorefrontRevalidator::class)->productId((int) $productImage->product_id);
    }
}
