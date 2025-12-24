# Ecommerce Module - Frontend Integration Guide

**Module:** Ecommerce
**Entities:** 15 (ShoppingCart, CartItem, CheckoutSession, PaymentTransaction, Wishlist, WishlistItem, ProductReview, Coupon, ShippingMethod, Currency, InventoryReservation, ProductQuestion, ProductAnswer, ProductComparison, ProductComparisonItem)
**Endpoints:** 75
**Base Path:** `/api/v1`

## Overview

The Ecommerce module provides a complete e-commerce solution including shopping carts, checkout flow, payment processing, wishlists, product reviews, coupons, multi-currency support, and inventory reservations.

## Core Entities

### 1. ShoppingCart

**Endpoint:** `/shopping-carts`
**Resource Type:** `shopping-carts`

#### TypeScript Interface

```typescript
type CartStatus = 'active' | 'abandoned' | 'converted' | 'expired';

interface ShoppingCart {
  id: string;
  sessionId: string | null;
  userId: string | null;
  status: CartStatus;
  expiresAt: string;
  totalAmount: number;
  currency: string;
  couponCode: string | null;
  discountAmount: number;
  taxAmount: number;
  shippingAmount: number;
  notes: string | null;
  metadata: Record<string, any> | null;

  // Calculated fields (read-only)
  itemsCount: number;
  subtotalAmount: number;
  finalTotal: number;
  isExpired: boolean;
  canApplyCoupon: boolean;

  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Calculated |
|---------------|-----------------|------|----------|----------|------------|
| `sessionId` | `session_id` | string | No | No | No |
| `userId` | `user_id` | string | No | No | No |
| `status` | `status` | string | Yes | Yes | No |
| `expiresAt` | `expires_at` | datetime | Yes | Yes | No |
| `totalAmount` | `total_amount` | number | Yes | Yes | No |
| `currency` | `currency` | string | Yes | Yes | No |
| `couponCode` | `coupon_code` | string | No | No | No |
| `discountAmount` | `discount_amount` | number | No | Yes | No |
| `taxAmount` | `tax_amount` | number | No | Yes | No |
| `shippingAmount` | `shipping_amount` | number | No | Yes | No |
| `itemsCount` | - | number | - | No | ✅ Yes |
| `subtotalAmount` | - | number | - | No | ✅ Yes |
| `finalTotal` | - | number | - | No | ✅ Yes |
| `isExpired` | - | boolean | - | No | ✅ Yes |
| `canApplyCoupon` | - | boolean | - | No | ✅ Yes |

#### Relationships

- `cartItems` → CartItem[] (hasMany)
- `user` → User (belongsTo)

---

### 2. CheckoutSession

**Endpoint:** `/checkout-sessions`
**Resource Type:** `checkout-sessions`

#### TypeScript Interface

```typescript
type CheckoutStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'expired';
type CheckoutStep = 'cart' | 'shipping' | 'payment' | 'confirmation';

interface CheckoutSession {
  id: string;
  shoppingCartId: number;
  userId: number;
  shippingMethodId: number | null;
  status: CheckoutStatus;
  step: CheckoutStep;

  // Contact information
  contactEmail: string;
  contactPhone: string | null;

  // Addresses (JSON objects)
  billingAddress: Address;
  shippingAddress: Address;

  // Payment
  paymentMethod: string | null;
  paymentIntentId: string | null;

  // Amounts
  subtotalAmount: number;
  shippingAmount: number;
  taxAmount: number;
  discountAmount: number;
  totalAmount: number;
  currency: string;

  notes: string | null;
  metadata: Record<string, any> | null;

  expiresAt: string;
  completedAt: string | null;

  // Calculated fields (read-only)
  isExpired: boolean;
  canProceedToPayment: boolean;
  timeRemaining: number; // seconds

  createdAt: string;
  updatedAt: string;
}

interface Address {
  street: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
}
```

#### Relationships

- `shoppingCart` → ShoppingCart (belongsTo)
- `user` → User (belongsTo)
- `shippingMethod` → ShippingMethod (belongsTo)
- `inventoryReservations` → InventoryReservation[] (hasMany)
- `paymentTransactions` → PaymentTransaction[] (hasMany)

---

### 3. PaymentTransaction

**Endpoint:** `/payment-transactions`
**Resource Type:** `payment-transactions`

#### TypeScript Interface

```typescript
type PaymentStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'refunded' | 'cancelled';
type PaymentGateway = 'stripe' | 'paypal' | 'mercadopago' | 'openpay' | 'conekta';

interface PaymentTransaction {
  id: string;
  checkoutSessionId: number;
  salesOrderId: number | null;
  arInvoiceId: number | null;
  transactionId: string;
  paymentGateway: PaymentGateway;
  paymentMethod: string;
  status: PaymentStatus;
  amount: number;
  currency: string;
  gatewayResponse: Record<string, any> | null;
  errorMessage: string | null;
  metadata: Record<string, any> | null;
  processedAt: string | null;

  // Calculated fields (read-only)
  isSuccessful: boolean;
  isFailed: boolean;
  isRefunded: boolean;

  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `checkoutSession` → CheckoutSession (belongsTo)
- `salesOrder` → SalesOrder (belongsTo)
- `arInvoice` → ARInvoice (belongsTo)

---

### 4. Wishlist

**Endpoint:** `/wishlists`
**Resource Type:** `wishlists`

#### TypeScript Interface

```typescript
interface Wishlist {
  id: string;
  userId: number;
  name: string;
  isDefault: boolean;
  isPublic: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `user` → User (belongsTo)
- `items` → WishlistItem[] (hasMany)
- `products` → Product[] (belongsToMany)

---

### 5. ProductReview

**Endpoint:** `/product-reviews`
**Resource Type:** `product-reviews`

#### TypeScript Interface

```typescript
type ReviewStatus = 'pending' | 'approved' | 'rejected';

interface ProductReview {
  id: string;
  productId: number;
  userId: number;
  rating: number; // 1-5
  title: string;
  comment: string;
  isVerifiedPurchase: boolean;
  helpfulCount: number;
  status: ReviewStatus;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `product` → Product (belongsTo)
- `user` → User (belongsTo)

---

### 6. Coupon

**Endpoint:** `/coupons`
**Resource Type:** `coupons`

#### TypeScript Interface

```typescript
type CouponType = 'percentage' | 'fixed' | 'free_shipping';

interface Coupon {
  id: string;
  code: string;
  name: string;
  description: string | null;
  couponType: CouponType;
  value: number;
  minAmount: number;
  maxAmount: number | null;
  maxUses: number;
  usedCount: number;
  startsAt: string;
  expiresAt: string;
  isActive: boolean;
  customerIds: number[];
  productIds: number[];
  categoryIds: number[];
  createdAt: string;
  updatedAt: string;
}
```

---

### 7. ShippingMethod

**Endpoint:** `/shipping-methods`
**Resource Type:** `shipping-methods`

#### TypeScript Interface

```typescript
interface ShippingMethod {
  id: string;
  name: string;
  code: string;
  description: string | null;
  carrier: string;
  baseCost: number;
  costPerKg: number;
  estimatedDaysMin: number;
  estimatedDaysMax: number;
  isActive: boolean;
  availableCountries: string[];
  metadata: Record<string, any> | null;

  // Calculated field (read-only)
  estimatedDelivery: string;

  createdAt: string;
  updatedAt: string;
}
```

---

### 8. Currency

**Endpoint:** `/currencies`
**Resource Type:** `currencies`

#### TypeScript Interface

```typescript
interface Currency {
  id: string;
  code: string; // ISO 4217 (USD, EUR, GBP, etc.)
  name: string;
  symbol: string;
  exchangeRate: number;
  isActive: boolean;
  isDefault: boolean;
  createdAt: string;
  updatedAt: string;
}
```

---

### 9. CartItem

**Endpoint:** `/cart-items`
**Resource Type:** `cart-items`

#### TypeScript Interface

```typescript
interface CartItem {
  id: string;
  shoppingCartId: string;
  productId: string;
  quantity: number;
  unitPrice: number;
  originalPrice: number;
  discountPercent: number;
  discountAmount: number;
  subtotal: number;
  taxRate: number;
  taxAmount: number;
  total: number;
  status: string;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `shoppingCart` → ShoppingCart (belongsTo)
- `product` → Product (belongsTo)

---

### 10. WishlistItem

**Endpoint:** `/wishlist-items`
**Resource Type:** `wishlist-items`

#### TypeScript Interface

```typescript
type Priority = 'low' | 'medium' | 'high';

interface WishlistItem {
  id: string;
  wishlistId: number;
  productId: number;
  quantity: number;
  priority: Priority;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `wishlistId` | `wishlist_id` | number | No | Yes |
| `productId` | `product_id` | number | No | Yes |
| `quantity` | `quantity` | number | Yes | No |
| `priority` | `priority` | string | Yes | Yes |

#### Relationships

- `wishlist` → Wishlist (belongsTo)
- `product` → Product (belongsTo)

---

### 11. InventoryReservation

**Endpoint:** `/inventory-reservations`
**Resource Type:** `inventory-reservations`

#### TypeScript Interface

```typescript
type ReservationStatus = 'active' | 'released' | 'fulfilled' | 'expired';

interface InventoryReservation {
  id: string;
  checkoutSessionId: number;
  stockId: number;
  productId: number;
  warehouseId: number;
  quantityReserved: number;
  status: ReservationStatus;
  expiresAt: string;
  releasedAt: string | null;
  fulfilledAt: string | null;
  notes: string | null;

  // Calculated fields (read-only)
  isExpired: boolean;
  isActive: boolean;
  timeRemaining: number;

  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `checkoutSessionId` | `checkout_session_id` | number | No | Yes |
| `stockId` | `stock_id` | number | No | No |
| `productId` | `product_id` | number | No | Yes |
| `warehouseId` | `warehouse_id` | number | No | Yes |
| `quantityReserved` | `quantity_reserved` | number | No | No |
| `status` | `status` | string | No | Yes |
| `expiresAt` | `expires_at` | datetime | No | No |

#### Relationships

- `checkoutSession` → CheckoutSession (belongsTo)
- `stock` → Stock (belongsTo)
- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)

---

### 12. ProductQuestion

**Endpoint:** `/product-questions`
**Resource Type:** `product-questions`

#### TypeScript Interface

```typescript
type QuestionStatus = 'pending' | 'approved' | 'rejected';

interface ProductQuestion {
  id: string;
  question: string;
  status: QuestionStatus;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `question` | `question` | string | Yes | No |
| `status` | `status` | string | Yes | Yes |
| `productId` | `product_id` | number | No | Yes |
| `userId` | `user_id` | number | No | Yes |

#### Relationships

- `product` → Product (belongsTo)
- `user` → User (belongsTo)
- `answers` → ProductAnswer[] (hasMany)

---

### 13. ProductAnswer

**Endpoint:** `/product-answers`
**Resource Type:** `product-answers`

#### TypeScript Interface

```typescript
interface ProductAnswer {
  id: string;
  answer: string;
  isVerified: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `answer` | `answer` | string | Yes | No |
| `isVerified` | `is_verified` | boolean | Yes | Yes |
| `questionId` | `question_id` | number | No | Yes |
| `userId` | `user_id` | number | No | Yes |

#### Relationships

- `question` → ProductQuestion (belongsTo)
- `user` → User (belongsTo)

---

### 14. ProductComparison

**Endpoint:** `/product-comparisons`
**Resource Type:** `product-comparisons`

#### TypeScript Interface

```typescript
interface ProductComparison {
  id: string;
  name: string;
  isPublic: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `name` | `name` | string | Yes | No |
| `isPublic` | `is_public` | boolean | Yes | Yes |
| `userId` | `user_id` | number | No | Yes |

#### Relationships

- `user` → User (belongsTo)
- `items` → ProductComparisonItem[] (hasMany)

---

### 15. ProductComparisonItem

**Endpoint:** `/product-comparison-items`
**Resource Type:** `product-comparison-items`

#### TypeScript Interface

```typescript
interface ProductComparisonItem {
  id: string;
  comparisonId: number;
  productId: number;
  position: number;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `comparisonId` | `comparison_id` | number | No | Yes |
| `productId` | `product_id` | number | No | Yes |
| `position` | `position` | number | Yes | No |

#### Relationships

- `comparison` → ProductComparison (belongsTo)
- `product` → Product (belongsTo)

---

## Complete Checkout Flow

### Step 1: Create/Update Shopping Cart

```javascript
// Add item to cart
const payload = {
  data: {
    type: "cart-items",
    attributes: {
      shoppingCartId: cartId,
      productId: 123,
      quantity: 2,
      unitPrice: 99.99,
      currency: "USD"
    }
  }
};

await fetch('/api/v1/cart-items', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

### Step 2: Apply Coupon (Optional)

```javascript
// Validate and apply coupon
const couponResponse = await fetch(
  `/api/v1/coupons?filter[code]=SUMMER2025`,
  { headers }
);

const coupon = await couponResponse.json();

// Update cart with coupon
const updatePayload = {
  data: {
    type: "shopping-carts",
    id: cartId,
    attributes: {
      couponCode: "SUMMER2025",
      discountAmount: calculateDiscount(coupon, cartTotal)
    }
  }
};

await fetch(`/api/v1/shopping-carts/${cartId}`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(updatePayload)
});
```

### Step 3: Create Checkout Session

```javascript
const checkoutPayload = {
  data: {
    type: "checkout-sessions",
    attributes: {
      shoppingCartId: parseInt(cartId),
      userId: currentUser.id,
      contactEmail: "customer@example.com",
      contactPhone: "+1234567890",
      billingAddress: {
        street: "123 Main St",
        city: "New York",
        state: "NY",
        postalCode: "10001",
        country: "US"
      },
      shippingAddress: {
        street: "123 Main St",
        city: "New York",
        state: "NY",
        postalCode: "10001",
        country: "US"
      },
      step: "shipping",
      status: "pending",
      currency: "USD"
    }
  }
};

const checkoutResponse = await fetch('/api/v1/checkout-sessions', {
  method: 'POST',
  headers,
  body: JSON.stringify(checkoutPayload)
});

const checkout = await checkoutResponse.json();
const sessionId = checkout.data.id;
```

### Step 4: Select Shipping Method

```javascript
// Get available shipping methods
const shippingMethods = await fetch(
  '/api/v1/shipping-methods?filter[isActive]=true',
  { headers }
);

// Update checkout with selected method
const shippingPayload = {
  data: {
    type: "checkout-sessions",
    id: sessionId,
    attributes: {
      shippingMethodId: 2,
      step: "payment"
    }
  }
};

await fetch(`/api/v1/checkout-sessions/${sessionId}`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(shippingPayload)
});
```

### Step 5: Process Payment

```javascript
// Create payment transaction
const paymentPayload = {
  data: {
    type: "payment-transactions",
    attributes: {
      checkoutSessionId: parseInt(sessionId),
      paymentGateway: "stripe",
      paymentMethod: "credit_card",
      amount: checkout.data.attributes.totalAmount,
      currency: "USD",
      status: "processing",
      metadata: {
        stripePaymentIntentId: "pi_123456"
      }
    }
  }
};

const paymentResponse = await fetch('/api/v1/payment-transactions', {
  method: 'POST',
  headers,
  body: JSON.stringify(paymentPayload)
});

// Update checkout session to completed
const completePayload = {
  data: {
    type: "checkout-sessions",
    id: sessionId,
    attributes: {
      status: "completed",
      step: "confirmation",
      completedAt: new Date().toISOString()
    }
  }
};

await fetch(`/api/v1/checkout-sessions/${sessionId}`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(completePayload)
});
```

---

## Wishlist Management

```javascript
// Create wishlist
async function createWishlist(name, isPublic = false) {
  const payload = {
    data: {
      type: "wishlists",
      attributes: {
        userId: currentUser.id,
        name: name,
        isDefault: false,
        isPublic: isPublic
      }
    }
  };

  const response = await fetch('/api/v1/wishlists', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}

// Add product to wishlist
async function addToWishlist(wishlistId, productId, priority = 'medium') {
  const payload = {
    data: {
      type: "wishlist-items",
      attributes: {
        wishlistId: wishlistId,
        productId: productId,
        priority: priority,
        notes: "Holiday gift idea"
      }
    }
  };

  const response = await fetch('/api/v1/wishlist-items', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}

// Get user's wishlists with products
async function getUserWishlists(userId) {
  const response = await fetch(
    `/api/v1/wishlists?filter[userId]=${userId}&include=items.product`,
    { headers }
  );

  return await response.json();
}
```

---

## Product Reviews

```javascript
// Submit product review
async function submitReview(productId, rating, title, comment) {
  const payload = {
    data: {
      type: "product-reviews",
      attributes: {
        productId: productId,
        userId: currentUser.id,
        rating: rating, // 1-5
        title: title,
        comment: comment,
        status: "pending" // Awaiting moderation
      }
    }
  };

  const response = await fetch('/api/v1/product-reviews', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}

// Get product reviews
async function getProductReviews(productId, filters = {}) {
  const params = new URLSearchParams({
    'filter[productId]': productId,
    'filter[status]': 'approved',
    'include': 'user',
    'sort': '-createdAt',
    ...filters
  });

  const response = await fetch(`/api/v1/product-reviews?${params}`, { headers });
  return await response.json();
}
```

---

## Multi-Currency Support

```javascript
// Get available currencies
async function getCurrencies() {
  const response = await fetch(
    '/api/v1/currencies?filter[isActive]=true',
    { headers }
  );

  return await response.json();
}

// Convert amount between currencies
async function convertCurrency(amount, fromCurrency, toCurrency) {
  const currenciesResponse = await fetch('/api/v1/currencies', { headers });
  const currencies = await currenciesResponse.json();

  const from = currencies.data.find(c => c.attributes.code === fromCurrency);
  const to = currencies.data.find(c => c.attributes.code === toCurrency);

  const amountInBase = amount / from.attributes.exchangeRate;
  const convertedAmount = amountInBase * to.attributes.exchangeRate;

  return convertedAmount;
}
```

---

## Permissions

### Role-Based Access

| Role | Cart | Checkout | Payment | Wishlist | Reviews | Coupons (Admin) |
|------|------|----------|---------|----------|---------|-----------------|
| **God** | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ CRUD |
| **Admin** | ✅ All | ✅ All | ✅ View | ✅ All | ✅ Moderate | ✅ CRUD |
| **Tech** | ✅ View | ✅ View | ✅ View | ✅ View | ✅ View | ❌ |
| **Customer** | ✅ Own | ✅ Own | ✅ Own | ✅ Own | ✅ Own | ❌ |

---

## Quick Reference

**Shopping & Checkout:**
- `/shopping-carts`, `/cart-items` - Cart management
- `/checkout-sessions` - Checkout process
- `/payment-transactions` - Payment processing
- `/inventory-reservations` - Stock reservations

**Features:**
- `/wishlists`, `/wishlist-items` - Wishlist management
- `/product-reviews` - Customer reviews
- `/coupons` - Discount codes
- `/shipping-methods` - Delivery options
- `/currencies` - Multi-currency support
- `/product-questions`, `/product-answers` - Q&A
- `/product-comparisons`, `/product-comparison-items` - Product comparison

**Related Modules:**
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Inventory Module](INVENTORY_FRONTEND_GUIDE.md) - Stock management
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Order fulfillment
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - Invoice generation
