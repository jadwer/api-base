# Ecommerce Module

## Entities

| Entity | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| ShoppingCart | `/api/v1/shopping-carts` | Yes | Customer carts (backend) |
| CartItem | `/api/v1/cart-items` | Yes | Cart line items |
| CheckoutSession | `/api/v1/checkout-sessions` | Yes | Checkout process |
| PaymentTransaction | `/api/v1/payment-transactions` | Yes | Payment processing |
| Wishlist | `/api/v1/wishlists` | Yes | Customer wishlists |
| WishlistItem | `/api/v1/wishlist-items` | Yes | Wishlist items |
| ProductReview | `/api/v1/product-reviews` | Read: No, Write: Yes | Product reviews |
| Coupon | `/api/v1/coupons` | Yes | Discount coupons |

---

## Guest Cart Strategy: LocalStorage

**IMPORTANTE**: Los carritos de invitados se manejan en **LocalStorage**, no en backend.

### Razones
1. **Sin basura en BD** - No se acumulan carritos abandonados
2. **Funciona offline** - El usuario puede agregar items sin conexión
3. **Sin gestión de sesiones** - No hay que vincular dispositivos
4. **Cross-device no es viable** - Sin auth, no hay forma confiable de vincular

### Flujo Guest → Usuario Autenticado

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Guest añade    │     │  Guest hace     │     │  Backend crea   │
│  items a        │────▶│  login/register │────▶│  carrito real   │
│  localStorage   │     │                 │     │  con items      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### LocalStorage Cart Structure

```typescript
interface LocalCartItem {
  productId: number;
  productVariantId: number | null;
  quantity: number;
  unitPrice: number;
  productName: string;      // Para mostrar sin fetch
  productImage: string;     // URL de imagen
  addedAt: string;          // ISO date
}

interface LocalCart {
  items: LocalCartItem[];
  couponCode: string | null;
  updatedAt: string;
}

// Storage key
const CART_KEY = 'ecommerce_cart';

// Example operations
function getLocalCart(): LocalCart {
  const data = localStorage.getItem(CART_KEY);
  return data ? JSON.parse(data) : { items: [], couponCode: null, updatedAt: new Date().toISOString() };
}

function saveLocalCart(cart: LocalCart): void {
  cart.updatedAt = new Date().toISOString();
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function addToLocalCart(item: LocalCartItem): void {
  const cart = getLocalCart();
  const existing = cart.items.find(i =>
    i.productId === item.productId &&
    i.productVariantId === item.productVariantId
  );

  if (existing) {
    existing.quantity += item.quantity;
  } else {
    cart.items.push(item);
  }

  saveLocalCart(cart);
}

function clearLocalCart(): void {
  localStorage.removeItem(CART_KEY);
}
```

### Sync on Login

```typescript
async function syncCartOnLogin(token: string): Promise<ShoppingCart> {
  const localCart = getLocalCart();

  if (localCart.items.length === 0) {
    // No local cart, fetch user's existing cart
    return await fetchUserCart(token);
  }

  // Create backend cart with local items
  const cart = await createCart(token, {
    status: 'active',
    couponCode: localCart.couponCode
  });

  // Add all items
  for (const item of localCart.items) {
    await addCartItem(token, {
      shoppingCartId: cart.id,
      productId: item.productId,
      productVariantId: item.productVariantId,
      quantity: item.quantity,
      unitPrice: item.unitPrice
    });
  }

  // Clear local cart
  clearLocalCart();

  return cart;
}
```

---

## Shopping Cart (Authenticated Users)

```typescript
type CartStatus = 'active' | 'abandoned' | 'converted' | 'expired';

interface ShoppingCart {
  id: string;
  userId: number;
  sessionId: string | null;
  status: CartStatus;
  totalAmount: number;
  discountAmount: number;
  taxAmount: number;
  shippingAmount: number;
  couponCode: string | null;
  currency: string;
  expiresAt: string | null;
  notes: string | null;

  // Computed (appends)
  itemsCount: number;
  subtotalAmount: number;
  finalTotal: number;
  isExpired: boolean;
  canApplyCoupon: boolean;

  createdAt: string;
  updatedAt: string;
}

// List user's carts
GET /api/v1/shopping-carts

// Create cart
POST /api/v1/shopping-carts
{
  "data": {
    "type": "shopping-carts",
    "attributes": {
      "status": "active",
      "currency": "MXN"
    }
  }
}

// Get cart with items
GET /api/v1/shopping-carts/{id}?include=cartItems

// Update cart
PATCH /api/v1/shopping-carts/{id}
{
  "data": {
    "type": "shopping-carts",
    "id": "1",
    "attributes": {
      "couponCode": "SAVE10",
      "discountAmount": 100.00
    }
  }
}

// Delete cart
DELETE /api/v1/shopping-carts/{id}
```

---

## Cart Item

```typescript
interface CartItem {
  id: string;
  shoppingCartId: number;
  productId: number;
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

// Add item to cart
POST /api/v1/cart-items
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "quantity": 2,
      "unitPrice": 150.00,
      "originalPrice": 150.00,
      "subtotal": 300.00,
      "total": 300.00
    },
    "relationships": {
      "shoppingCart": {
        "data": { "type": "shopping-carts", "id": "1" }
      },
      "product": {
        "data": { "type": "products", "id": "5" }
      }
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
      "subtotal": 450.00,
      "total": 450.00
    }
  }
}

// Remove item
DELETE /api/v1/cart-items/{id}
```

---

## Checkout Session

```typescript
type CheckoutStatus = 'pending' | 'address_required' | 'shipping_required' |
                      'payment_required' | 'processing' | 'completed' |
                      'failed' | 'cancelled' | 'expired';

interface CheckoutSession {
  id: string;
  shoppingCartId: number;
  userId: number;
  status: CheckoutStatus;

  // Customer info
  customerEmail: string;
  customerPhone: string | null;

  // Addresses (JSON)
  shippingAddress: Address | null;
  billingAddress: Address | null;

  // Shipping
  shippingMethodId: number | null;
  shippingAmount: number;

  // Amounts
  subtotalAmount: number;
  discountAmount: number;
  taxAmount: number;
  totalAmount: number;
  currency: string;

  // Payment
  paymentMethod: string | null;
  paymentIntentId: string | null;

  // Result
  salesOrderId: number | null;
  completedAt: string | null;
  expiresAt: string;

  createdAt: string;
  updatedAt: string;
}

// Start checkout
POST /api/v1/checkout-sessions
{
  "data": {
    "type": "checkout-sessions",
    "attributes": {
      "shoppingCartId": 1,
      "customerEmail": "customer@example.com",
      "shippingAddress": {
        "street": "Av. Reforma 123",
        "city": "CDMX",
        "state": "CDMX",
        "postalCode": "06600",
        "country": "MX"
      },
      "billingAddress": {
        "street": "Av. Reforma 123",
        "city": "CDMX",
        "state": "CDMX",
        "postalCode": "06600",
        "country": "MX"
      }
    }
  }
}

// Update checkout (add shipping method)
PATCH /api/v1/checkout-sessions/{id}
{
  "data": {
    "type": "checkout-sessions",
    "id": "1",
    "attributes": {
      "shippingMethodId": 2,
      "shippingAmount": 150.00,
      "status": "payment_required"
    }
  }
}

// Get checkout with relations
GET /api/v1/checkout-sessions/{id}?include=shoppingCart,shippingMethod
```

---

## Payment Transaction (Stripe)

```typescript
type PaymentStatus = 'pending' | 'processing' | 'authorized' | 'captured' |
                     'succeeded' | 'failed' | 'cancelled' | 'refunded' |
                     'partially_refunded';

interface PaymentTransaction {
  id: string;
  checkoutSessionId: number;
  salesOrderId: number | null;
  arInvoiceId: number | null;

  transactionId: string;      // Stripe payment intent ID
  gateway: 'stripe' | 'mock';
  paymentMethod: string;      // 'card', 'oxxo', etc.

  amount: number;
  currency: string;
  status: PaymentStatus;

  gatewayResponse: Record<string, any>;
  errorCode: string | null;
  errorMessage: string | null;

  authorizedAt: string | null;
  capturedAt: string | null;
  failedAt: string | null;
  refundedAt: string | null;

  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

### Stripe Integration Flow

```typescript
// 1. Create checkout session first
const checkoutSession = await createCheckoutSession(cartId, addresses);

// 2. Backend creates Stripe PaymentIntent via CheckoutService
//    This happens automatically when checkout session is created

// 3. Get client_secret from checkout session
const clientSecret = checkoutSession.attributes.paymentIntentId;

// 4. Confirm with Stripe.js
const stripe = await loadStripe(STRIPE_PUBLIC_KEY);
const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
  payment_method: {
    card: cardElement,
    billing_details: {
      name: 'Customer Name',
      email: 'customer@example.com'
    }
  }
});

if (error) {
  showError(error.message);
  return;
}

// 5. Update checkout session status
await updateCheckoutSession(checkoutSession.id, {
  status: 'processing'
});

// 6. Backend completes checkout via event/webhook
//    Creates: SalesOrder, ARInvoice automatically
```

---

## Wishlist

```typescript
interface Wishlist {
  id: string;
  userId: number;
  name: string;
  description: string | null;
  isPublic: boolean;
  isDefault: boolean;
  shareToken: string | null;
  itemCount: number;
  createdAt: string;
  updatedAt: string;
}

interface WishlistItem {
  id: string;
  wishlistId: number;
  productId: number;
  productVariantId: number | null;
  priority: number;
  notes: string | null;
  notifyOnSale: boolean;
  notifyOnStock: boolean;
  addedAt: string;
  createdAt: string;
  updatedAt: string;
}

// Get user wishlists
GET /api/v1/wishlists

// Create wishlist
POST /api/v1/wishlists
{
  "data": {
    "type": "wishlists",
    "attributes": {
      "name": "Birthday Ideas",
      "isPublic": false
    }
  }
}

// Add item to wishlist
POST /api/v1/wishlist-items
{
  "data": {
    "type": "wishlist-items",
    "attributes": {
      "priority": 1,
      "notifyOnSale": true
    },
    "relationships": {
      "wishlist": {
        "data": { "type": "wishlists", "id": "1" }
      },
      "product": {
        "data": { "type": "products", "id": "10" }
      }
    }
  }
}

// Get wishlist with items
GET /api/v1/wishlists/{id}?include=wishlistItems.product
```

---

## Product Review

**Note**: Reading approved reviews is PUBLIC (no auth required).

```typescript
type ReviewStatus = 'pending' | 'approved' | 'rejected';

interface ProductReview {
  id: string;
  productId: number;
  userId: number;
  rating: number;            // 1-5
  title: string | null;
  content: string | null;
  pros: string[] | null;
  cons: string[] | null;
  isVerifiedPurchase: boolean;
  status: ReviewStatus;
  helpfulCount: number;
  reportCount: number;
  createdAt: string;
  updatedAt: string;
}

// Get reviews for product (PUBLIC - no auth)
GET /api/v1/product-reviews?filter[product_id]=1&filter[status]=approved&sort=-createdAt

// Create review (requires auth)
POST /api/v1/product-reviews
{
  "data": {
    "type": "product-reviews",
    "attributes": {
      "productId": 1,
      "rating": 5,
      "title": "Excellent product!",
      "content": "Exceeded my expectations...",
      "pros": ["Quality", "Fast shipping"],
      "cons": ["Expensive"]
    }
  }
}
```

---

## Coupons

```typescript
type DiscountType = 'percentage' | 'fixed_amount' | 'free_shipping';

interface Coupon {
  id: string;
  code: string;
  name: string;
  description: string | null;
  discountType: DiscountType;
  discountValue: number;
  minOrderAmount: number | null;
  maxDiscountAmount: number | null;
  usageLimit: number | null;
  usageCount: number;
  usageLimitPerUser: number | null;
  startDate: string | null;
  endDate: string | null;
  isActive: boolean;
  appliesToAllProducts: boolean;
  createdAt: string;
  updatedAt: string;
}

// Validate coupon (call before applying)
// This is done by attempting to update cart with couponCode
PATCH /api/v1/shopping-carts/{id}
{
  "data": {
    "type": "shopping-carts",
    "id": "1",
    "attributes": {
      "couponCode": "SAVE10"
    }
  }
}

// If invalid, will return 422 with error
// If valid, cart will be updated with discount
```

---

## Product Recommendations (PUBLIC)

```typescript
// Related products
GET /api/v1/products/{id}/related

// Frequently bought together
GET /api/v1/products/{id}/frequently-bought-together

// Trending products
GET /api/v1/products/trending

// Popular products
GET /api/v1/products/popular

// New arrivals
GET /api/v1/products/new-arrivals

// Personalized (requires auth)
GET /api/v1/products/recommended
```

---

## Complete Checkout Flow

```typescript
async function completeCheckout(shippingAddress: Address, cardElement: any) {
  const token = getAuthToken();

  // 1. Sync local cart to backend (if coming from guest)
  let cart = await syncCartOnLogin(token);

  // 2. Create checkout session
  const sessionResponse = await fetch('/api/v1/checkout-sessions', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json'
    },
    body: JSON.stringify({
      data: {
        type: 'checkout-sessions',
        attributes: {
          shoppingCartId: parseInt(cart.id),
          customerEmail: user.email,
          shippingAddress,
          billingAddress: shippingAddress
        }
      }
    })
  });
  const { data: checkoutSession } = await sessionResponse.json();

  // 3. Select shipping method
  await fetch(`/api/v1/checkout-sessions/${checkoutSession.id}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify({
      data: {
        type: 'checkout-sessions',
        id: checkoutSession.id,
        attributes: {
          shippingMethodId: selectedShippingMethod.id,
          shippingAmount: selectedShippingMethod.price
        }
      }
    })
  });

  // 4. Get updated session with payment intent
  const updatedSession = await fetch(
    `/api/v1/checkout-sessions/${checkoutSession.id}`,
    { headers }
  ).then(r => r.json());

  // 5. Confirm with Stripe
  const stripe = await loadStripe(STRIPE_PUBLIC_KEY);
  const { error, paymentIntent } = await stripe.confirmCardPayment(
    updatedSession.data.attributes.paymentIntentId,
    {
      payment_method: {
        card: cardElement,
        billing_details: { name: user.name, email: user.email }
      }
    }
  );

  if (error) {
    throw new Error(error.message);
  }

  // 6. Wait for backend to process (webhook or poll)
  const result = await pollCheckoutStatus(checkoutSession.id);

  // 7. Clear local cart and navigate
  clearLocalCart();

  return {
    salesOrderId: result.salesOrderId,
    orderNumber: result.orderNumber
  };
}

async function pollCheckoutStatus(sessionId: string, maxAttempts = 10): Promise<any> {
  for (let i = 0; i < maxAttempts; i++) {
    const session = await fetch(`/api/v1/checkout-sessions/${sessionId}`, { headers })
      .then(r => r.json());

    if (session.data.attributes.status === 'completed') {
      return session.data.attributes;
    }

    if (session.data.attributes.status === 'failed') {
      throw new Error('Payment failed');
    }

    await new Promise(resolve => setTimeout(resolve, 1000));
  }

  throw new Error('Checkout timeout');
}
```

---

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| **Guest Cart** | LocalStorage only | Sync on login |
| **Auth Required** | All cart endpoints need token | Redirect to login for checkout |
| **Cart Expiration** | Backend carts can expire | Check `isExpired` computed field |
| **Stock Validation** | Validated at checkout | Show stock errors |
| **Coupon Limits** | One coupon per cart | Clear previous before applying |
| **Review Moderation** | Reviews need approval | Show pending status |
| **Verified Purchase** | Auto-set if bought product | Show badge on reviews |
| **Payment Timeout** | Stripe intents expire | Handle 402 errors |

---

## Error Codes

| HTTP | Meaning | Action |
|------|---------|--------|
| 401 | Not authenticated | Redirect to login |
| 403 | Not authorized (not owner) | Show error |
| 404 | Cart/item not found | Refresh cart |
| 422 | Validation error | Show field errors |
| 422 | Coupon invalid | Show coupon error |
| 402 | Payment required/failed | Show payment error |
