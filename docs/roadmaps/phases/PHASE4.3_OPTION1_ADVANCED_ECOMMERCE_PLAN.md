# Phase 4.3 Option 1: Advanced Ecommerce Features - Implementation Plan

**Status:** 📋 Planning
**Start Date:** TBD
**Estimated Duration:** 3-4 days
**Complexity:** Low-Medium (2/5)
**Priority:** 🥇 HIGHEST (Recommended)
**Dependencies:** Phase 4.1 (Ecommerce Enhancement) ✅ Complete

---

## Objective

Enhance the existing Ecommerce module with customer engagement features to increase conversion rates and customer satisfaction. Implement product reviews & ratings, wishlist functionality, product recommendations engine, and multi-currency support.

**Business Value:**
- Increase customer engagement (reviews, wishlists)
- Boost conversion rates (recommendations)
- Expand market reach (multi-currency)
- Build trust (verified purchase reviews)

---

## Implementation Plan

### Stage 1: Product Reviews & Ratings (Day 1, 4-5 hours)

#### 1.1 Database Migration

**New Table: `product_reviews`**
```sql
CREATE TABLE product_reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rating INTEGER NOT NULL, -- 1-5 stars
    title VARCHAR(255) NOT NULL,
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending', -- pending, approved, rejected
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product_reviews_product (product_id),
    INDEX idx_product_reviews_status (status),
    INDEX idx_product_reviews_rating (rating)
);
```

#### 1.2 Model & Relationships

**ProductReview Model:**
```php
class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'rating', 'title', 'comment',
        'is_verified_purchase', 'helpful_count', 'status'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo;
    public function user(): BelongsTo;

    // Scopes
    public function scopeApproved($query);
    public function scopeVerifiedPurchase($query);
}
```

**Enhance Product Model:**
Add calculated attributes via scopes:
- `average_rating` - Average of approved reviews
- `total_reviews` - Count of approved reviews

#### 1.3 API Endpoints

**Public:**
```
GET  /api/v1/product-reviews              List approved reviews
GET  /api/v1/product-reviews/{id}         Show review
GET  /api/v1/products/{id}/reviews        Product's reviews
```

**Authenticated (Customer):**
```
POST   /api/v1/product-reviews            Create review
PATCH  /api/v1/product-reviews/{id}       Update own review
DELETE /api/v1/product-reviews/{id}       Delete own review
POST   /api/v1/product-reviews/{id}/helpful  Mark as helpful
```

**Admin:**
```
PATCH /api/v1/product-reviews/{id}/moderate   Approve/reject review
```

#### 1.4 Business Logic

**ReviewService:**
- `createReview()` - Create new review, auto-check verified purchase
- `moderateReview()` - Approve/reject (admin only)
- `calculateProductRating()` - Aggregate rating stats
- `markHelpful()` - Increment helpful count

**Validation Rules:**
- Rating: 1-5 integer required
- Title: required, max 255 chars
- Comment: optional, text
- One review per user per product
- Can only review if authenticated

#### 1.5 Testing

Create 5 test files:
- `ProductReviewIndexTest.php`
- `ProductReviewShowTest.php`
- `ProductReviewStoreTest.php`
- `ProductReviewUpdateTest.php`
- `ProductReviewDestroyTest.php`

**Test Scenarios:**
- Guest can view approved reviews
- Customer can create review
- Cannot review same product twice
- Verified purchase badge works
- Owner can update/delete
- Admin can moderate
- Rating aggregation accurate

---

### Stage 2: Wishlist System (Day 2, 4-5 hours)

#### 2.1 Database Migrations

**Table: `wishlists`**
```sql
CREATE TABLE wishlists (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) DEFAULT 'My Wishlist',
    is_default BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_wishlists_user (user_id),
    INDEX idx_wishlists_default (is_default)
);
```

**Table: `wishlist_items`**
```sql
CREATE TABLE wishlist_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    wishlist_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INTEGER DEFAULT 1,
    priority VARCHAR(50) DEFAULT 'medium', -- low, medium, high
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_product (wishlist_id, product_id),
    INDEX idx_wishlist_items_wishlist (wishlist_id)
);
```

#### 2.2 Models

**Wishlist Model:**
```php
class Wishlist extends Model
{
    protected $fillable = ['user_id', 'name', 'is_default', 'is_public'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo;
    public function items(): HasMany;
    public function products(): BelongsToMany;
}
```

**WishlistItem Model:**
```php
class WishlistItem extends Model
{
    protected $fillable = ['wishlist_id', 'product_id', 'quantity', 'priority', 'notes'];

    public function wishlist(): BelongsTo;
    public function product(): BelongsTo;
}
```

#### 2.3 API Endpoints

**Customer:**
```
GET    /api/v1/wishlists                      User's wishlists
POST   /api/v1/wishlists                      Create wishlist
GET    /api/v1/wishlists/{id}                 Show wishlist
PATCH  /api/v1/wishlists/{id}                 Update wishlist
DELETE /api/v1/wishlists/{id}                 Delete wishlist
POST   /api/v1/wishlists/{id}/items           Add product
DELETE /api/v1/wishlists/{id}/items/{itemId}  Remove product
POST   /api/v1/wishlists/{id}/move-to-cart    Move all to cart
```

**Public:**
```
GET /api/v1/wishlists/{id}/public   View shared wishlist (if public)
```

#### 2.4 Business Logic

**WishlistService:**
- `getOrCreateDefault()` - Auto-create default wishlist
- `addProduct()` - Add product to wishlist
- `removeProduct()` - Remove product from wishlist
- `moveToCart()` - Move all items to shopping cart
- `shareWishlist()` - Generate shareable link

#### 2.5 Testing

Create 10 test files (5 for Wishlist + 5 for WishlistItem)

**Test Scenarios:**
- User can create multiple wishlists
- Default wishlist auto-created
- Add/remove products
- Cannot add duplicate products
- Public wishlists shareable
- Private wishlists require auth
- Move to cart works
- Owner-only access enforced

---

### Stage 3: Product Recommendations (Day 3, 3-4 hours)

#### 3.1 Recommendation Service

**RecommendationEngine Service:**
```php
class RecommendationEngine
{
    // Same category/brand, similar price
    public function getRelatedProducts(Product $product, int $limit = 6): Collection;

    // Products bought together in same orders
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 4): Collection;

    // Based on user's purchase history
    public function getPersonalizedRecommendations(User $user, int $limit = 12): Collection;

    // Most purchased in last 30 days
    public function getTrendingProducts(int $limit = 12): Collection;

    // Highest rated + most reviews
    public function getPopularProducts(int $limit = 12): Collection;

    // Recently created products
    public function getNewArrivals(int $limit = 12): Collection;
}
```

#### 3.2 Recommendation Logic

**Related Products:**
- Same category as base product
- Similar price range (±30%)
- Order by rating DESC, sales DESC

**Frequently Bought Together:**
```sql
SELECT p.*, COUNT(*) as frequency
FROM products p
JOIN sales_order_items soi1 ON soi1.product_id = p.id
JOIN sales_order_items soi2 ON soi2.sales_order_id = soi1.sales_order_id
WHERE soi2.product_id = :base_product_id
  AND soi1.product_id != :base_product_id
GROUP BY p.id
ORDER BY frequency DESC
LIMIT :limit
```

**Personalized:**
- Get user's purchase categories
- Find top-rated products in those categories
- Exclude already purchased
- Order by rating DESC

**Trending:**
- Products with most sales in last 30 days
- ORDER BY sales_count DESC

**Popular:**
- average_rating >= 4.0
- total_reviews >= 5
- ORDER BY average_rating DESC, total_reviews DESC

#### 3.3 API Endpoints

**Public:**
```
GET /api/v1/products/{id}/related                    Related products
GET /api/v1/products/{id}/frequently-bought-together  Bought together
GET /api/v1/products/trending                         Trending
GET /api/v1/products/popular                          Popular
GET /api/v1/products/new-arrivals                     New
```

**Authenticated:**
```
GET /api/v1/products/recommended    Personalized for user
```

#### 3.4 Testing

Create 1 test file: `RecommendationEngineTest.php`

**Test Scenarios:**
- Related products from same category
- Bought together uses order data
- Personalized based on history
- Trending products are recent
- Popular has minimum reviews
- New arrivals by date

---

### Stage 4: Multi-Currency Support (Day 4, 3-4 hours)

#### 4.1 Database Migration

**Table: `currencies`**
```sql
CREATE TABLE currencies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(3) NOT NULL UNIQUE, -- ISO 4217
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
    is_base BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_currencies_code (code),
    INDEX idx_currencies_active (is_active)
);
```

**Seed Default Currencies:**
```php
Currency::create(['code' => 'MXN', 'name' => 'Peso Mexicano', 'symbol' => '$', 'exchange_rate' => 1.000000, 'is_base' => true]);
Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 17.500000]);
Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 19.250000]);
```

#### 4.2 Currency Model

```php
class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'exchange_rate', 'is_base', 'is_active'];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Convert from base currency to this currency
    public function convertFromBase(float $amount): float
    {
        return $amount / $this->exchange_rate;
    }

    // Convert from this currency to base currency
    public function convertToBase(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }
}
```

#### 4.3 Currency Service

**CurrencyService:**
```php
class CurrencyService
{
    public function convert(float $amount, string $from, string $to): float;
    public function getActiveCurrencies(): Collection;
    public function getBaseCurrency(): Currency;
    public function updateExchangeRates(array $rates): void;
    public function formatAmount(float $amount, string $currencyCode): string;
}
```

#### 4.4 Product Pricing

**Enhance ProductResource:**
```php
public function attributes($request): array
{
    $currency = $request->input('currency', 'MXN');
    $price = $this->price;

    if ($currency !== 'MXN') {
        $price = app(CurrencyService::class)->convert($price, 'MXN', $currency);
    }

    return [
        'name' => $this->name,
        'price' => round($price, 2),
        'currency' => $currency,
        // ... other fields
    ];
}
```

#### 4.5 API Endpoints

**Public:**
```
GET /api/v1/currencies                      List active currencies
GET /api/v1/currencies/{id}                 Show currency
GET /api/v1/products?currency=USD           Products with USD prices
```

**Admin:**
```
POST   /api/v1/currencies                  Create currency
PATCH  /api/v1/currencies/{id}             Update exchange rate
DELETE /api/v1/currencies/{id}             Deactivate currency
```

#### 4.6 Checkout Integration

**CheckoutSession Enhancement:**
- Add `currency` parameter to initiate checkout
- Store currency in `checkout_sessions.currency`
- Convert cart totals to selected currency
- PaymentTransaction uses same currency

#### 4.7 Testing

Create 5 test files for Currency + 3 for CurrencyService

**Test Scenarios:**
- List active currencies
- Convert between currencies
- Get product prices in USD/EUR
- Checkout with non-base currency
- Admin update exchange rates
- Only one base currency allowed

---

## Database Schema Summary

**New Tables:** 4
- `product_reviews` (11 columns, 5 indexes)
- `wishlists` (6 columns, 3 indexes)
- `wishlist_items` (7 columns, 3 indexes + 1 unique)
- `currencies` (8 columns, 3 indexes + 1 unique)

**Enhanced Tables:** 1
- `products` - Virtual attributes (`average_rating`, `total_reviews`)

---

## API Endpoints Summary

| Category | Public | Authenticated | Admin | Total |
|----------|--------|---------------|-------|-------|
| Product Reviews | 3 | 4 | 1 | 8 |
| Wishlists | 1 | 8 | 0 | 9 |
| Recommendations | 5 | 1 | 0 | 6 |
| Currencies | 2 | 0 | 3 | 5 |
| **TOTAL** | **11** | **13** | **4** | **28** |

---

## Testing Summary

| Entity | Test Files | Estimated Tests |
|--------|-----------|-----------------|
| ProductReview | 5 | 25+ |
| Wishlist | 5 | 25+ |
| WishlistItem | 5 | 25+ |
| Currency | 5 | 25+ |
| RecommendationEngine | 1 | 6+ |
| CurrencyService | 1 | 6+ |
| **TOTAL** | **22** | **112+** |

---

## Permissions Structure

**ProductReviews:**
- `ecommerce.product-reviews.index` → All (guest)
- `ecommerce.product-reviews.show` → All (guest)
- `ecommerce.product-reviews.store` → Customer+
- `ecommerce.product-reviews.update` → Owner, Admin, God
- `ecommerce.product-reviews.destroy` → Owner, Admin, God

**Wishlists:**
- `ecommerce.wishlists.*` → Customer (owner), Admin, God

**Currencies:**
- `ecommerce.currencies.index/show` → All (guest)
- `ecommerce.currencies.store/update/destroy` → Admin, God

---

## Risk Assessment

**Low Risk:** ✅
- ProductReview (standard CRUD)
- Wishlist (similar to ShoppingCart)
- Currency conversion (well-understood)

**Medium Risk:** ⚠️
- Recommendation engine performance
- Rating aggregation accuracy

**Mitigation:**
- Add database indexes
- Cache recommendation results (1 hour TTL)
- Use eager loading (prevent N+1)
- Test with realistic data (1000+ products)

---

## Success Criteria

**Functional:**
- [ ] Customers can write/edit product reviews
- [ ] Reviews show verified purchase badge
- [ ] Products display average rating
- [ ] Customers can create wishlists
- [ ] Wishlist items movable to cart
- [ ] Public wishlists shareable
- [ ] Related products display
- [ ] Personalized recommendations work
- [ ] Prices viewable in USD/MXN/EUR
- [ ] Checkout supports multi-currency

**Technical:**
- [ ] 22 test files created
- [ ] 112+ tests passing
- [ ] Zero N+1 queries
- [ ] API < 200ms (p95)
- [ ] JSON:API 1.1 compliant
- [ ] Proper camelCase→snake_case mapping

---

## Post-Implementation

**Documentation:**
- [ ] Update `DEVELOPMENT_ROADMAP.md`
- [ ] Create `PHASE4.3_OPTION1_COMPLETE.md`
- [ ] Update `DATABASE_SCHEMA_REFERENCE.md`
- [ ] Create frontend integration guide

**Optional Next:** Phase 4.4 - Loyalty & Promotions (2-3 days)
- Loyalty points system
- Advanced promotion engine
- Gift cards
- Subscription products

---

## Effort Breakdown

| Stage | Duration | Complexity |
|-------|----------|------------|
| Product Reviews | 4-5 hours | Low-Medium |
| Wishlists | 4-5 hours | Low |
| Recommendations | 3-4 hours | Medium |
| Multi-Currency | 3-4 hours | Low-Medium |
| Testing & QA | 4-6 hours | Medium |
| Documentation | 2-3 hours | Low |
| **TOTAL** | **20-27 hours** | **3-4 days** |

---

**Document Status:** Planning Complete
**Last Updated:** 2025-10-29
**Next Action:** Review and approve, then begin Stage 1
