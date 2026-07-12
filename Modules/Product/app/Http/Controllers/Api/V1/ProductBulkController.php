<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;

class ProductBulkController extends Controller
{
    /**
     * Bulk toggle is_active for specific products.
     *
     * POST /api/v1/products/bulk-toggle-active
     */
    public function bulkToggleActive(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        $count = Product::whereIn('id', $request->input('product_ids'))
            ->update(['is_active' => $request->boolean('is_active')]);

        $status = $request->boolean('is_active') ? 'activados' : 'desactivados';

        return response()->json([
            'message' => "{$count} productos {$status}",
            'affected_count' => $count,
        ]);
    }

    /**
     * Bulk toggle is_active for all products of a brand.
     *
     * POST /api/v1/products/bulk-toggle-by-brand
     */
    public function bulkToggleByBrand(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'brand_id' => 'required|integer|exists:brands,id',
            'is_active' => 'required|boolean',
        ]);

        $count = Product::where('brand_id', $request->input('brand_id'))
            ->update(['is_active' => $request->boolean('is_active')]);

        $status = $request->boolean('is_active') ? 'activados' : 'desactivados';

        return response()->json([
            'message' => "{$count} productos {$status}",
            'affected_count' => $count,
        ]);
    }

    /**
     * Bulk toggle is_active for all products of a category.
     *
     * POST /api/v1/products/bulk-toggle-by-category
     */
    public function bulkToggleByCategory(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'is_active' => 'required|boolean',
        ]);

        $count = Product::where('category_id', $request->input('category_id'))
            ->update(['is_active' => $request->boolean('is_active')]);

        $status = $request->boolean('is_active') ? 'activados' : 'desactivados';

        return response()->json([
            'message' => "{$count} productos {$status}",
            'affected_count' => $count,
        ]);
    }

    /**
     * Toggle brand is_active, optionally cascading to its products.
     *
     * POST /api/v1/brands/{brand}/toggle-active
     */
    public function toggleBrandActive(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('brands.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'is_active' => 'required|boolean',
            'include_products' => 'sometimes|boolean',
        ]);

        $isActive = $request->boolean('is_active');
        $includeProducts = $request->boolean('include_products', false);
        $productsAffected = 0;

        DB::transaction(function () use ($brand, $isActive, $includeProducts, &$productsAffected) {
            $brand->is_active = $isActive;
            $brand->save();

            if ($includeProducts) {
                $productsAffected = Product::where('brand_id', $brand->id)
                    ->update(['is_active' => $isActive]);
            }
        });

        $status = $isActive ? 'activada' : 'desactivada';

        return response()->json([
            'message' => "Marca {$status}" . ($productsAffected > 0 ? " con {$productsAffected} productos" : ''),
            'brand_updated' => true,
            'products_affected' => $productsAffected,
        ]);
    }

    /**
     * Toggle category is_active, optionally cascading to its products.
     *
     * POST /api/v1/categories/{category}/toggle-active
     */
    public function toggleCategoryActive(Request $request, Category $category): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('categories.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'is_active' => 'required|boolean',
            'include_products' => 'sometimes|boolean',
        ]);

        $isActive = $request->boolean('is_active');
        $includeProducts = $request->boolean('include_products', false);
        $productsAffected = 0;

        DB::transaction(function () use ($category, $isActive, $includeProducts, &$productsAffected) {
            $category->is_active = $isActive;
            $category->save();

            if ($includeProducts) {
                $productsAffected = Product::where('category_id', $category->id)
                    ->update(['is_active' => $isActive]);
            }
        });

        $status = $isActive ? 'activada' : 'desactivada';

        return response()->json([
            'message' => "Categoria {$status}" . ($productsAffected > 0 ? " con {$productsAffected} productos" : ''),
            'category_updated' => true,
            'products_affected' => $productsAffected,
        ]);
    }

    /**
     * Bulk assign a category to all products of a brand.
     *
     * POST /api/v1/products/bulk-assign-category-by-brand
     */
    public function bulkAssignCategoryByBrand(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'brand_id' => 'required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $brandId = $request->input('brand_id');
        $categoryId = $request->input('category_id');
        $affected = 0;

        DB::transaction(function () use ($brandId, $categoryId, &$affected) {
            $affected = Product::where('brand_id', $brandId)
                ->update(['category_id' => $categoryId]);
        });

        return response()->json([
            'message' => "{$affected} productos reasignados a la categoria",
            'affected_count' => $affected,
        ]);
    }

    /**
     * Bulk price update by brand (percentage increase/decrease).
     *
     * POST /api/v1/products/bulk-price-update
     */
    public function bulkPriceUpdate(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'brand_id' => 'required|integer|exists:brands,id',
            'percentage' => 'required|numeric|min:0.01|max:100',
            'operation' => 'required|string|in:increase,decrease',
        ]);

        $brandId = $request->input('brand_id');
        $percentage = (float) $request->input('percentage');
        $operation = $request->input('operation');

        $multiplier = $operation === 'increase'
            ? (1 + $percentage / 100)
            : (1 - $percentage / 100);

        $count = 0;

        DB::transaction(function () use ($brandId, $multiplier, &$count) {
            $products = Product::where('brand_id', $brandId)->get();

            foreach ($products as $product) {
                $product->price = round($product->price * $multiplier, 2);
                $product->save();
                $count++;
            }
        });

        $operationLabel = $operation === 'increase' ? 'aumentados' : 'disminuidos';

        return response()->json([
            'message' => "Precios {$operationLabel} un {$percentage}% para {$count} productos",
            'affected_count' => $count,
            'operation' => $operation,
            'percentage' => $percentage,
        ]);
    }
}
