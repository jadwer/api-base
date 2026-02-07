<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\Services\RecommendationEngine;
use Modules\Product\Models\Product;

class ProductRecommendationController extends Controller
{
    protected RecommendationEngine $recommendationEngine;

    public function __construct(RecommendationEngine $recommendationEngine)
    {
        $this->recommendationEngine = $recommendationEngine;
    }

    /**
     * Transform Product model to JSON:API format
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
                'imgPath' => $product->img_path,
                'isActive' => $product->is_active,
                'averageRating' => $product->average_rating,
                'totalReviews' => $product->total_reviews,
                'totalSales' => $product->total_sales,
            ],
        ];
    }

    /**
     * Get related products for a given product
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function related(int $id, Request $request): JsonResponse
    {
        $product = Product::findOrFail($id);
        $limit = $request->input('limit', 6);

        $relatedProducts = $this->recommendationEngine->getRelatedProducts($product, $limit);

        return response()->json([
            'data' => $relatedProducts->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $relatedProducts->count(),
                'type' => 'related',
            ],
        ]);
    }

    /**
     * Get frequently bought together products
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function frequentlyBoughtTogether(int $id, Request $request): JsonResponse
    {
        $product = Product::findOrFail($id);
        $limit = $request->input('limit', 4);

        $products = $this->recommendationEngine->getFrequentlyBoughtTogether($product, $limit);

        return response()->json([
            'data' => $products->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'frequently_bought_together',
            ],
        ]);
    }

    /**
     * Get trending products (most purchased in last 30 days)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 12);

        $products = $this->recommendationEngine->getTrendingProducts($limit);

        return response()->json([
            'data' => $products->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'trending',
            ],
        ]);
    }

    /**
     * Get popular products (highest rated with minimum reviews)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 12);

        $products = $this->recommendationEngine->getPopularProducts($limit);

        return response()->json([
            'data' => $products->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'popular',
            ],
        ]);
    }

    /**
     * Get new arrival products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function newArrivals(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 12);

        $products = $this->recommendationEngine->getNewArrivals($limit);

        return response()->json([
            'data' => $products->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'new_arrivals',
            ],
        ]);
    }

    /**
     * Get personalized recommendations for authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function personalized(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 12);

        $products = $this->recommendationEngine->getPersonalizedRecommendations($user, $limit);

        return response()->json([
            'data' => $products->map(fn($p) => $this->transformProduct($p)),
            'meta' => [
                'count' => $products->count(),
                'type' => 'personalized',
            ],
        ]);
    }
}
