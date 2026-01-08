# Ecommerce Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| ShoppingCart | `/api/v1/shopping-carts` | Customer carts |
| CartItem | `/api/v1/cart-items` | Cart line items |
| CheckoutSession | `/api/v1/checkout-sessions` | Checkout process |
| OnlinePayment | `/api/v1/online-payments` | Payment processing |
| Wishlist | `/api/v1/wishlists` | Customer wishlists |
| WishlistItem | `/api/v1/wishlist-items` | Wishlist items |
| ProductReview | `/api/v1/product-reviews` | Product reviews |
| Coupon | `/api/v1/coupons` | Discount coupons |

## Shopping Cart

```typescript
type CartStatus = 'active' | 'abandoned' | 'converted' | 'expired';

interface ShoppingCart {
  id: string;
  userId: number | null;      // Null for guest carts
  sessionId: string;          // For guest tracking
  status: CartStatus;
  subtotal: number;
  discountAmount: number;
  taxAmount: number;
  total: number;
  couponCode: string | null;
  expiresAt: string;
  createdAt: string;
}

// Get or create cart
GET /api/v1/shopping-carts/current
POST /api/v1/shopping-carts/get-or-create

// Get cart with items
GET /api/v1/shopping-carts/{id}?include=items.product

// Merge guest cart after login
POST /api/v1/shopping-carts/merge
{
  "guest_session_id": "guest-session-123"
}
```

## Cart Item

```typescript
interface CartItem {
  id: string;
  shoppingCartId: number;
  productId: number;
  productVariantId: number | null;
  quantity: number;
  unitPrice: number;
  discountAmount: number;
  total: number;
  createdAt: string;
}

// Add item to cart
POST /api/v1/cart-items
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "shoppingCartId": 1,
      "productId": 5,
      "quantity": 2,
      "unitPrice": 150.00,
      "total": 300.00
    }
  }
}

// Update quantity
PATCH /api/v1/cart-items/{id}
{
  "data": {
    "type": "cart-items",
    "id": "1",
    "attributes": {
      "quantity": 3,
      "total": 450.00
    }
  }
}

// Remove item
DELETE /api/v1/cart-items/{id}
```

## Checkout Session

```typescript
type CheckoutStatus = 'pending' | 'payment_pending' | 'completed' | 'failed' | 'cancelled';

interface CheckoutSession {
  id: string;
  shoppingCartId: number;
  contactId: number | null;
  status: CheckoutStatus;

  // Address info
  shippingAddressId: number | null;
  billingAddressId: number | null;

  // Amounts
  subtotal: number;
  shippingAmount: number;
  taxAmount: number;
  discountAmount: number;
  total: number;

  // Payment
  paymentMethod: string | null;
  paymentIntentId: string | null;  // Stripe

  // Result
  salesOrderId: number | null;
  completedAt: string | null;

  createdAt: string;
}

// Start checkout
POST /api/v1/checkout-sessions
{
  "data": {
    "type": "checkout-sessions",
    "attributes": {
      "shoppingCartId": 1,
      "shippingAddressId": 5,
      "billingAddressId": 5
    }
  }
}

// Update checkout (add shipping method, etc.)
PATCH /api/v1/checkout-sessions/{id}
{
  "data": {
    "type": "checkout-sessions",
    "attributes": {
      "shippingMethod": "express",
      "shippingAmount": 150.00
    }
  }
}
```

## Online Payment (Stripe)

```typescript
interface OnlinePayment {
  id: string;
  checkoutSessionId: number;
  provider: 'stripe';
  amount: number;
  currency: string;
  status: 'pending' | 'processing' | 'succeeded' | 'failed' | 'refunded';
  providerPaymentId: string | null;   // Stripe payment intent ID
  providerData: Record<string, any>;
  errorMessage: string | null;
  paidAt: string | null;
  createdAt: string;
}

// Create payment intent (Stripe)
POST /api/v1/online-payments/create-intent
{
  "checkout_session_id": 1,
  "amount": 11600.00,
  "currency": "mxn"
}

// Response
{
  "client_secret": "pi_xxx_secret_xxx",  // Use with Stripe.js
  "payment_intent_id": "pi_xxx"
}

// Confirm payment (after Stripe.js confirmation)
POST /api/v1/online-payments/confirm
{
  "payment_intent_id": "pi_xxx"
}

// Response (creates order automatically)
{
  "status": "succeeded",
  "sales_order_id": 15,
  "order_number": "SO-XXXXXXXX",
  "ar_invoice_id": 20
}
```

### Stripe Integration Flow

```typescript
// Frontend flow
async function checkout(checkoutSessionId: number) {
  // 1. Create payment intent
  const { client_secret } = await createPaymentIntent(checkoutSessionId);

  // 2. Confirm with Stripe.js
  const stripe = await loadStripe(STRIPE_PUBLIC_KEY);
  const { error, paymentIntent } = await stripe.confirmCardPayment(client_secret, {
    payment_method: {
      card: cardElement,
      billing_details: { name: 'Customer Name' }
    }
  });

  if (error) {
    showError(error.message);
    return;
  }

  // 3. Confirm on backend
  const result = await confirmPayment(paymentIntent.id);

  // 4. Navigate to order confirmation
  navigateTo(`/orders/${result.sales_order_id}`);
}
```

## Wishlist

```typescript
interface Wishlist {
  id: string;
  userId: number;
  name: string;              // "My Wishlist", "Birthday Ideas"
  isPublic: boolean;
  shareToken: string | null; // For sharing
  createdAt: string;
}

interface WishlistItem {
  id: string;
  wishlistId: number;
  productId: number;
  productVariantId: number | null;
  addedAt: string;
  notes: string | null;
}

// Get user wishlists
GET /api/v1/wishlists?filter[user_id]=current

// Add to wishlist
POST /api/v1/wishlist-items
{
  "data": {
    "type": "wishlist-items",
    "attributes": {
      "wishlistId": 1,
      "productId": 10
    }
  }
}

// Move to cart
POST /api/v1/wishlist-items/{id}/move-to-cart
```

## Product Review

```typescript
interface ProductReview {
  id: string;
  productId: number;
  userId: number;
  rating: number;            // 1-5
  title: string | null;
  content: string | null;
  isVerifiedPurchase: boolean;
  isApproved: boolean;
  createdAt: string;
}

// Get reviews for product
GET /api/v1/product-reviews?filter[product_id]=1&filter[is_approved]=true&sort=-createdAt

// Create review
POST /api/v1/product-reviews
{
  "data": {
    "type": "product-reviews",
    "attributes": {
      "productId": 1,
      "rating": 5,
      "title": "Great product!",
      "content": "Exceeded my expectations..."
    }
  }
}

// Get product rating summary
GET /api/v1/products/{id}/rating-summary

// Response
{
  "averageRating": 4.5,
  "totalReviews": 120,
  "distribution": {
    "5": 80,
    "4": 25,
    "3": 10,
    "2": 3,
    "1": 2
  }
}
```

## Coupons

```typescript
type DiscountType = 'percentage' | 'fixed_amount' | 'free_shipping';

interface Coupon {
  id: string;
  code: string;              // SAVE10
  name: string;
  discountType: DiscountType;
  discountValue: number;
  minOrderAmount: number | null;
  maxDiscount: number | null;
  usageLimit: number | null;
  usageCount: number;
  startDate: string | null;
  endDate: string | null;
  isActive: boolean;
}

// Apply coupon to cart
POST /api/v1/shopping-carts/{id}/apply-coupon
{
  "coupon_code": "SAVE10"
}

// Response
{
  "valid": true,
  "discount_amount": 100.00,
  "new_total": 900.00,
  "message": "10% discount applied"
}

// OR
{
  "valid": false,
  "error": "Coupon expired"
}

// Remove coupon
POST /api/v1/shopping-carts/{id}/remove-coupon
```

## Product Recommendations

```typescript
// Get recommendations for product
GET /api/v1/products/{id}/recommendations

// Response
{
  "frequently_bought_together": [
    { "id": 5, "name": "iPhone Case", "price": 29.99 }
  ],
  "similar_products": [
    { "id": 10, "name": "Samsung Galaxy", "price": 1199.99 }
  ],
  "customers_also_viewed": [...]
}

// Get personalized recommendations (for logged-in user)
GET /api/v1/recommendations/personalized
```

## Complete Checkout Flow

```typescript
async function completeCheckout(cart: ShoppingCart, shippingAddress: Address) {
  // 1. Create checkout session
  const session = await fetch('/api/v1/checkout-sessions', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'checkout-sessions',
        attributes: {
          shoppingCartId: cart.id,
          shippingAddressId: shippingAddress.id,
          billingAddressId: shippingAddress.id
        }
      }
    })
  });
  const { data: checkoutSession } = await session.json();

  // 2. Create payment intent
  const intent = await fetch('/api/v1/online-payments/create-intent', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      checkout_session_id: checkoutSession.id,
      amount: checkoutSession.attributes.total,
      currency: 'mxn'
    })
  });
  const { client_secret } = await intent.json();

  // 3. Confirm with Stripe
  const stripe = await loadStripe(STRIPE_PUBLIC_KEY);
  const { paymentIntent } = await stripe.confirmCardPayment(client_secret, {
    payment_method: { card: cardElement }
  });

  // 4. Confirm on backend (creates order + invoice)
  const result = await fetch('/api/v1/online-payments/confirm', {
    method: 'POST',
    headers,
    body: JSON.stringify({ payment_intent_id: paymentIntent.id })
  });

  return result.json();
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Cart Expiration | Carts expire after 24h | Show timer |
| Stock Validation | Check stock at checkout | Show stock errors |
| Guest Checkout | Allow without login | Merge cart on login |
| Coupon Limits | One coupon per cart | Validate before apply |
| Review Verification | Mark if bought product | Show verified badge |
| Payment Timeout | Payment intent expires | Handle expiration |
