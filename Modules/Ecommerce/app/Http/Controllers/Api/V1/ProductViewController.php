<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\Models\ProductView;
use Modules\Product\Models\Product;

class ProductViewController extends Controller
{
    /**
     * Record a product view.
     * Deduplication: skip if same product viewed < 30 min ago by same user/session.
     *
     * POST /api/v1/products/{id}/track-view
     */
    public function trackView(int $id, Request $request): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $user = $request->user('sanctum');
        $sessionId = $request->input('session_id');

        // Deduplication: don't record same product view within 30 minutes
        $query = ProductView::where('product_id', $product->id)
            ->where('viewed_at', '>=', now()->subMinutes(30));

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            // No user and no session_id, use IP for dedup
            $ip = $request->ip();
            $recentView = ProductView::where('product_id', $product->id)
                ->whereNull('user_id')
                ->whereNull('session_id')
                ->where('viewed_at', '>=', now()->subMinutes(30))
                ->where('ip_address', $ip)
                ->exists();

            if (!$recentView) {
                ProductView::create([
                    'product_id' => $product->id,
                    'ip_address' => $ip,
                    'viewed_at' => now(),
                ]);
            }
            return response()->json(['message' => 'View tracked']);
        }

        if (!$query->exists()) {
            ProductView::create([
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'session_id' => $user ? null : $sessionId,
                'viewed_at' => now(),
            ]);
        }

        return response()->json(['message' => 'View tracked']);
    }

    /**
     * Get recently viewed products for authenticated user.
     *
     * GET /api/v1/products/recently-viewed
     */
    public function recentlyViewed(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $limit = min((int) $request->input('limit', 8), 50);

        // Get distinct product IDs ordered by most recent view
        $productIds = ProductView::where('user_id', $user->id)
            ->select('product_id')
            ->selectRaw('MAX(viewed_at) as last_viewed')
            ->groupBy('product_id')
            ->orderByDesc('last_viewed')
            ->limit($limit)
            ->pluck('product_id')
            ->toArray();

        if (empty($productIds)) {
            return response()->json([
                'data' => [],
                'meta' => ['count' => 0, 'type' => 'recently_viewed'],
            ]);
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })
            ->values();

        return response()->json([
            'data' => $products->map(fn ($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'recently_viewed',
            ],
        ]);
    }

    /**
     * Transform product to JSON:API-like format.
     * Same format as ProductRecommendationController.
     */
    protected function transformProduct(Product $product): array
    {
        return [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'price' => $product->price,
                'cost' => $product->cost,
                'imgPath' => $product->img_path,
                'imageUrl' => $product->img_url ?? null,
                'isActive' => $product->is_active,
                'averageRating' => $product->average_rating,
                'totalReviews' => $product->total_reviews,
                'totalSales' => $product->total_sales,
            ],
        ];
    }
}
