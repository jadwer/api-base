# E2E Testing Guide for Frontend

**Last Updated:** 2026-01-06
**Version:** v1.0

This document describes the E2E integration tests and how frontend should implement equivalent flows.

---

## Available E2E Tests

### 1. Online Sales Flow (OnlineSalesE2ETest)

**Location:** `Modules/Ecommerce/tests/Integration/OnlineSalesE2ETest.php`

This test covers the complete online sales workflow from cart to invoice.

#### Test Cases

| Test | Description | Frontend Implementation |
|------|-------------|------------------------|
| `test_complete_online_sales_flow` | Cart -> Checkout -> SalesOrder -> Contact creation | Full checkout flow |
| `test_contact_is_reused_for_returning_customer` | Existing contact reuse by email | Return customer handling |
| `test_expired_checkout_cannot_be_completed` | Expired session rejection | Session timeout handling |
| `test_cart_migration_preserves_items` | Guest cart to user migration | Login with cart items |
| `test_ar_invoice_created_from_sales_order_event` | Automatic invoice creation | Order confirmation |

---

## Flow 1: Complete Online Sales

### Backend Flow
```
1. User browses products (GET /api/v1/products)
2. User adds to cart (POST /api/v1/cart-items)
3. User initiates checkout (POST /api/v1/checkout-sessions)
4. User confirms payment (PATCH /api/v1/checkout-sessions/{id})
5. Backend completes checkout:
   - Creates/reuses Contact from user email
   - Creates SalesOrder with items
   - Marks cart and session as completed
   - Dispatches SalesOrderCompleted event
   - Event listener creates ARInvoice with GL posting
```

### Frontend Implementation

#### Step 1: Shopping Cart
```javascript
// Add item to cart
POST /api/v1/cart-items
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "quantity": 2,
      "unitPrice": 500.00
    },
    "relationships": {
      "shoppingCart": { "data": { "type": "shopping-carts", "id": "1" } },
      "product": { "data": { "type": "products", "id": "123" } }
    }
  }
}
```

#### Step 2: Create Checkout Session
```javascript
// User must be authenticated
POST /api/v1/checkout-sessions
{
  "data": {
    "type": "checkout-sessions",
    "attributes": {
      "contactEmail": "user@example.com",
      "contactPhone": "+52 55 1234 5678",
      "shippingAddress": {
        "street": "Av. Reforma 123",
        "city": "CDMX",
        "state": "CDMX",
        "postalCode": "06600",
        "country": "MX"
      },
      "billingAddress": { ... }
    },
    "relationships": {
      "shoppingCart": { "data": { "type": "shopping-carts", "id": "1" } }
    }
  }
}
```

#### Step 3: Process Payment (Stripe)
```javascript
// Create payment intent on backend
POST /api/v1/checkout-sessions/{id}/payment-intent

// Frontend uses Stripe.js to confirm payment
const { paymentIntent, error } = await stripe.confirmCardPayment(
  clientSecret,
  { payment_method: paymentMethodId }
);

// Update checkout session with payment confirmation
PATCH /api/v1/checkout-sessions/{id}
{
  "data": {
    "type": "checkout-sessions",
    "id": "{id}",
    "attributes": {
      "status": "payment_confirmed",
      "paymentIntentId": "pi_xxx"
    }
  }
}
```

#### Step 4: Complete Checkout
```javascript
// Backend automatically:
// 1. Creates Contact if not exists (or reuses by email)
// 2. Creates SalesOrder with status 'confirmed'
// 3. Creates SalesOrderItems from CartItems
// 4. Marks cart as 'completed'
// 5. Marks checkout session as 'completed'
// 6. Dispatches SalesOrderCompleted event
// 7. Listener creates ARInvoice with GL posting

// Frontend receives:
{
  "data": {
    "type": "checkout-sessions",
    "id": "1",
    "attributes": {
      "status": "completed",
      "salesOrderId": 123
    }
  }
}
```

#### Step 5: Order Confirmation
```javascript
// Get the created sales order
GET /api/v1/sales-orders/123?include=items,contact,arInvoice

// Response includes:
{
  "data": {
    "type": "sales-orders",
    "id": "123",
    "attributes": {
      "orderNumber": "SO-000123",
      "status": "confirmed",
      "orderSource": "ecommerce",
      "subtotal": 1000.00,
      "taxAmount": 160.00,
      "totalAmount": 1160.00,
      "invoicingStatus": "invoiced"
    },
    "relationships": {
      "arInvoice": { "data": { "type": "ar-invoices", "id": "456" } }
    }
  }
}
```

---

## Flow 2: Guest to User Cart Migration

### Backend Flow
```
1. Guest browses with session_id in cart
2. Guest adds items to cart
3. Guest registers/logs in
4. Backend migrates cart from session to user
5. Cart items are preserved
```

### Frontend Implementation
```javascript
// 1. Create guest cart with session ID
POST /api/v1/shopping-carts
{
  "data": {
    "type": "shopping-carts",
    "attributes": {
      "sessionId": "sess_guest_abc123",
      "currency": "MXN"
    }
  }
}

// 2. Add items (same as authenticated flow)

// 3. User logs in - send cart ID to migrate
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "xxx",
  "cartId": 1  // Optional: cart to migrate
}

// 4. Backend updates cart:
//    - Sets user_id
//    - Clears session_id
//    - Preserves all cart items
```

---

## Flow 3: Returning Customer

### Backend Behavior
When a user checks out with an email that already exists as a Contact:
1. Backend finds existing Contact by email
2. Reuses that Contact for the SalesOrder
3. No duplicate Contact is created

### Frontend Considerations
- Show "Welcome back" message if contact exists
- Pre-fill shipping address from contact's default address
- Show previous order history

```javascript
// Check if contact exists
GET /api/v1/contacts?filter[email]=user@example.com

// If exists, use for checkout
// If not, backend creates automatically during checkout
```

---

## Flow 4: Expired Session Handling

### Backend Behavior
- Checkout sessions expire after 30 minutes (default)
- Expired sessions cannot be completed even with payment_confirmed status
- Frontend must create new session

### Frontend Implementation
```javascript
// Check session before payment
GET /api/v1/checkout-sessions/{id}

// If expired (expiresAt < now), show error and create new session
if (new Date(session.attributes.expiresAt) < new Date()) {
  showError('Your session has expired. Please start checkout again.');
  // Redirect to cart
}
```

---

## API Endpoints Summary

### Shopping Cart
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/shopping-carts | List carts (filtered by user) |
| POST | /api/v1/shopping-carts | Create cart |
| GET | /api/v1/shopping-carts/{id} | Get cart with items |
| PATCH | /api/v1/shopping-carts/{id} | Update cart |
| DELETE | /api/v1/shopping-carts/{id} | Delete cart |

### Cart Items
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/cart-items | List items in cart |
| POST | /api/v1/cart-items | Add item to cart |
| PATCH | /api/v1/cart-items/{id} | Update quantity |
| DELETE | /api/v1/cart-items/{id} | Remove item |

### Checkout Sessions
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/v1/checkout-sessions | Create checkout session |
| GET | /api/v1/checkout-sessions/{id} | Get session status |
| PATCH | /api/v1/checkout-sessions/{id} | Update session (payment confirmation) |

### Sales Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/sales-orders | List orders (filtered by contact) |
| GET | /api/v1/sales-orders/{id} | Get order details |

---

## TypeScript Interfaces

```typescript
interface ShoppingCart {
  id: string;
  userId?: number;
  sessionId?: string;
  status: 'active' | 'completed' | 'abandoned';
  currency: string;
  createdAt: string;
  updatedAt: string;
}

interface CartItem {
  id: string;
  shoppingCartId: number;
  productId: number;
  quantity: number;
  unitPrice: number;
  total: number;
}

interface CheckoutSession {
  id: string;
  shoppingCartId: number;
  userId?: number;
  status: 'pending' | 'payment_pending' | 'payment_confirmed' | 'completed' | 'expired' | 'cancelled';
  contactEmail: string;
  contactPhone?: string;
  shippingAddress: Address;
  billingAddress?: Address;
  subtotalAmount: number;
  taxAmount: number;
  shippingAmount: number;
  totalAmount: number;
  currency: string;
  paymentIntentId?: string;
  salesOrderId?: number;
  expiresAt: string;
}

interface SalesOrder {
  id: string;
  orderNumber: string;
  contactId: number;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  orderSource: 'manual' | 'ecommerce' | 'api';
  subtotal: number;
  taxAmount: number;
  discountTotal: number;
  totalAmount: number;
  invoicingStatus: 'pending' | 'invoiced' | 'cancelled';
  arInvoiceId?: number;
}

interface Address {
  street: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
}
```

---

## Error Handling

### Common Errors

| Code | Message | Frontend Action |
|------|---------|----------------|
| 422 | "Checkout session cannot be completed in current state" | Session expired, create new |
| 422 | "Cart is empty" | Redirect to products |
| 422 | "Product out of stock" | Show stock error, update cart |
| 403 | "Unauthorized" | Redirect to login |
| 500 | "Payment processing failed" | Show payment error, retry |

### Error Response Format
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "Checkout session cannot be completed in current state",
      "source": { "pointer": "/data/attributes/status" }
    }
  ]
}
```

---

## Running E2E Tests

### Backend
```bash
# Run all E2E tests
php artisan test Modules/Ecommerce/tests/Integration/

# Run specific test
php artisan test Modules/Ecommerce/tests/Integration/OnlineSalesE2ETest.php

# Run with verbose output
php artisan test Modules/Ecommerce/tests/Integration/ --verbose
```

### Frontend (Suggested)
```bash
# Using Cypress or Playwright
npx cypress run --spec "cypress/e2e/checkout.cy.ts"
npx playwright test checkout.spec.ts
```
