<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Carbon\Carbon;

class RecommendationEngine
{
    /**
     * Get related products based on same category/brand and similar price
     *
     * @param Product $product
     * @param int $limit
     * @return Collection
     */
    public function getRelatedProducts(Product $product, int $limit = 6): Collection
    {
        $priceMin = $product->price * 0.7; // -30%
        $priceMax = $product->price * 1.3; // +30%

        return Product::where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                $query->where('category_id', $product->category_id)
                    ->orWhere('brand_id', $product->brand_id);
            })
            ->whereBetween('price', [$priceMin, $priceMax])
            ->where('is_active', true)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    /**
     * Get products frequently bought together with the given product
     *
     * @param Product $product
     * @param int $limit
     * @return Collection
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 4): Collection
    {
        // Get product IDs that were bought together, ordered by frequency
        $productIds = DB::table('sales_order_items as soi1')
            ->select('soi1.product_id', DB::raw('COUNT(*) as frequency'))
            ->join('sales_order_items as soi2', 'soi2.sales_order_id', '=', 'soi1.sales_order_id')
            ->join('products', 'products.id', '=', 'soi1.product_id')
            ->where('soi2.product_id', $product->id)
            ->where('soi1.product_id', '!=', $product->id)
            ->where('products.is_active', true)
            ->groupBy('soi1.product_id')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->pluck('soi1.product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return $productIds->search($product->id);
            })
            ->values();
    }

    /**
     * Get personalized recommendations based on user's purchase history
     * Uses checkout_sessions (which have user_id) to track user purchases
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 12): Collection
    {
        // Get categories from user's completed checkout sessions
        $purchasedCategories = DB::table('checkout_sessions')
            ->join('sales_orders', 'sales_orders.checkout_session_id', '=', 'checkout_sessions.id')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('checkout_sessions.user_id', $user->id)
            ->where('checkout_sessions.status', 'completed')
            ->pluck('products.category_id')
            ->unique()
            ->toArray();

        // Get products already purchased by user
        $purchasedProducts = DB::table('checkout_sessions')
            ->join('sales_orders', 'sales_orders.checkout_session_id', '=', 'checkout_sessions.id')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('checkout_sessions.user_id', $user->id)
            ->where('checkout_sessions.status', 'completed')
            ->pluck('sales_order_items.product_id')
            ->toArray();

        if (empty($purchasedCategories)) {
            // Fallback to popular products if no purchase history
            return $this->getPopularProducts($limit);
        }

        return Product::whereIn('category_id', $purchasedCategories)
            ->whereNotIn('id', $purchasedProducts)
            ->where('is_active', true)
            ->whereNotNull('average_rating')
            ->orderByDesc('average_rating')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trending products (most purchased in last 30 days)
     *
     * @param int $limit
     * @return Collection
     */
    public function getTrendingProducts(int $limit = 12): Collection
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Get product IDs with sales count, ordered by popularity
        $productIds = DB::table('sales_order_items')
            ->select('sales_order_items.product_id', DB::raw('COUNT(sales_order_items.id) as recent_sales_count'))
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->where('sales_orders.created_at', '>=', $thirtyDaysAgo)
            ->where('products.is_active', true)
            ->groupBy('sales_order_items.product_id')
            ->orderByDesc('recent_sales_count')
            ->limit($limit)
            ->pluck('sales_order_items.product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return $productIds->search($product->id);
            })
            ->values();
    }

    /**
     * Get popular products (highest rated with minimum reviews)
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularProducts(int $limit = 12): Collection
    {
        return Product::where('is_active', true)
            ->where('average_rating', '>=', 4.0)
            ->where('total_reviews', '>=', 5)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_reviews')
            ->limit($limit)
            ->get();
    }

    /**
     * Get new arrivals (recently created products)
     *
     * @param int $limit
     * @return Collection
     */
    public function getNewArrivals(int $limit = 12): Collection
    {
        return Product::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
