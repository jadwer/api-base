<?php

namespace Modules\Product\Observers;

use Modules\Product\Models\ProductImage;

class ProductImageObserver
{
    /**
     * Handle the ProductImage "created" event.
     * If it's the first image for the product, make it primary.
     */
    public function created(ProductImage $productImage): void
    {
        $count = ProductImage::where('product_id', $productImage->product_id)->count();

        if ($count === 1) {
            // First image - auto-set as primary
            $productImage->updateQuietly(['is_primary' => true]);
            $this->syncProductImgPath($productImage);
        } elseif ($productImage->is_primary) {
            $this->unsetOtherPrimaries($productImage);
            $this->syncProductImgPath($productImage);
        }
    }

    /**
     * Handle the ProductImage "updated" event.
     * If is_primary changed to true, unset others and sync.
     */
    public function updated(ProductImage $productImage): void
    {
        if ($productImage->isDirty('is_primary') && $productImage->is_primary) {
            $this->unsetOtherPrimaries($productImage);
            $this->syncProductImgPath($productImage);
        }
    }

    /**
     * Handle the ProductImage "deleted" event.
     * If deleted image was primary, promote next or clear img_path.
     */
    public function deleted(ProductImage $productImage): void
    {
        if ($productImage->is_primary) {
            $next = ProductImage::where('product_id', $productImage->product_id)
                ->orderBy('sort_order')
                ->first();

            if ($next) {
                $next->updateQuietly(['is_primary' => true]);
                $this->syncProductImgPath($next);
            } else {
                // No more images - clear product img_path
                $productImage->product()->update(['img_path' => null]);
            }
        }
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
     * Sync the primary image's file_path to Product.img_path.
     */
    private function syncProductImgPath(ProductImage $productImage): void
    {
        $productImage->product()->update(['img_path' => $productImage->file_path]);
    }
}
