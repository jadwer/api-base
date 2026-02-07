<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product;

class CatalogController extends Controller
{
    /**
     * Get public product catalog
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['unit', 'category', 'brand']);

        // Basic filtering
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        if ($request->has('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->brand . '%');
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'name');
        $sortDirection = in_array($request->get('direction', 'asc'), ['asc', 'desc']) ? $request->get('direction', 'asc') : 'asc';

        $allowedSorts = ['name', 'created_at', 'updated_at', 'price'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 50); // Max 50 items
        $products = $query->paginate($perPage);

        // Transform to public format
        $data = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'full_description' => $product->full_description,
                'price' => $product->price,
                'cost' => $product->cost,
                'iva' => $product->iva,
                'img_path' => $product->img_path,
                'datasheet_path' => $product->datasheet_path,
                'unit' => $product->unit ? [
                    'id' => $product->unit->id,
                    'name' => $product->unit->name,
                    'symbol' => $product->unit->symbol,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Get single product for public catalog
     *
     * @param int $id
     * @return JsonResponse
     */
    public function product(int $id): JsonResponse
    {
        $product = Product::with(['unit', 'category', 'brand'])
            ->where('id', $id)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'full_description' => $product->full_description,
                'price' => $product->price,
                'cost' => $product->cost,
                'iva' => $product->iva,
                'img_path' => $product->img_path,
                'datasheet_path' => $product->datasheet_path,
                'unit' => $product->unit ? [
                    'id' => $product->unit->id,
                    'name' => $product->unit->name,
                    'symbol' => $product->unit->symbol,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString(),
            ]
        ]);
    }

    /**
     * Get categories for public catalog
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = \Modules\Product\Models\Category::query()
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products_count' => $category->products_count,
                ];
            })
        ]);
    }

    /**
     * Get brands for public catalog
     *
     * @return JsonResponse
     */
    public function brands(): JsonResponse
    {
        $brands = \Modules\Product\Models\Brand::query()
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $brands->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'description' => $brand->description,
                    'products_count' => $brand->products_count,
                ];
            })
        ]);
    }
}