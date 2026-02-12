<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Models\ProductImage;

class ProductImageCustomController extends Controller
{
    /**
     * Reorder images for a product.
     *
     * POST /api/v1/product-images/reorder
     * Body: { "image_ids": [3, 1, 2] }
     */
    public function reorder(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('product-images.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'required|integer|exists:product_images,id',
        ]);

        $imageIds = $request->input('image_ids');

        // Verify all images belong to same product
        $productIds = ProductImage::whereIn('id', $imageIds)->pluck('product_id')->unique();
        if ($productIds->count() !== 1) {
            return response()->json(['error' => 'All images must belong to the same product'], 422);
        }

        // Update sort_order based on array position
        foreach ($imageIds as $index => $imageId) {
            ProductImage::where('id', $imageId)->update(['sort_order' => $index]);
        }

        return response()->json([
            'message' => 'Images reordered successfully',
        ]);
    }

    /**
     * Set an image as the primary image.
     *
     * POST /api/v1/product-images/{productImage}/set-primary
     */
    public function setPrimary(Request $request, ProductImage $productImage): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('product-images.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $productImage->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Image set as primary successfully',
            'data' => [
                'type' => 'product-images',
                'id' => (string) $productImage->id,
                'attributes' => [
                    'filePath' => $productImage->file_path,
                    'imageUrl' => $productImage->image_url,
                    'altText' => $productImage->alt_text,
                    'sortOrder' => $productImage->sort_order,
                    'isPrimary' => true,
                ],
            ],
        ]);
    }
}
