# Phase 4.1 - Ecommerce Enhancement Implementation Plan

**Start Date:** 2025-10-29
**Estimated Duration:** 2-3 days
**Status:** 🔄 In Progress
**Branch:** `lwm`

---

## Executive Summary

Phase 4.1 will enhance the existing Ecommerce module with complete checkout flow, payment gateway integration, order tracking, and shipping management to create a production-ready e-commerce solution.

### Current State Analysis

**Existing Entities (Already Complete):**
- ✅ ShoppingCart (with calculated totals, discounts, tax, shipping)
- ✅ CartItem (products in cart)
- ✅ Coupon (discount codes)
- ✅ Sales Module (SalesOrder, Customer)
- ✅ Inventory Module (Stock, Warehouses)
- ✅ Finance Module (AR Invoices, Receipts)

**What's Missing:**
- ❌ Checkout flow and process management
- ❌ Payment gateway integration
- ❌ Order status tracking system
- ❌ Shipping method management
- ❌ Inventory reservation during checkout
- ❌ Email notification system
- ❌ Cart-to-Order conversion logic

---

## Implementation Stages

### Stage 1: Checkout Flow Foundation (Day 1, 3-4 hours)

#### 1.1 Database Migrations

**New Tables:**

1. **shipping_methods**
```sql
- id
- name (string, required) e.g., "Standard Shipping", "Express"
- code (string, unique) e.g., "standard", "express"
- carrier (string, nullable) e.g., "FedEx", "DHL"
- base_cost (decimal)
- cost_per_kg (decimal, nullable)
- estimated_days_min (integer)
- estimated_days_max (integer)
- is_active (boolean, default true)
- available_countries (json, nullable)
- description (text, nullable)
- metadata (json, nullable)
- timestamps
```

2. **checkout_sessions**
```sql
- id
- shopping_cart_id (foreign key)
- user_id (foreign key, nullable)
- status (enum: 'initiated', 'payment_pending', 'payment_confirmed', 'completed', 'failed', 'expired')
- step (enum: 'address', 'shipping', 'payment', 'confirmation')
- shipping_method_id (foreign key, nullable)
- billing_address (json)
- shipping_address (json)
- contact_email (string)
- contact_phone (string, nullable)
- payment_method (string, nullable) e.g., "stripe", "paypal"
- payment_intent_id (string, nullable) - from payment gateway
- subtotal_amount (decimal)
- shipping_amount (decimal)
- tax_amount (decimal)
- discount_amount (decimal)
- total_amount (decimal)
- currency (string, default 'MXN')
- expires_at (datetime)
- completed_at (datetime, nullable)
- notes (text, nullable)
- metadata (json, nullable)
- timestamps
```

3. **payment_transactions**
```sql
- id
- checkout_session_id (foreign key, nullable)
- sales_order_id (foreign key, nullable) - linked after order creation
- ar_receipt_id (foreign key, nullable) - linked to Finance module
- transaction_id (string, unique) - from payment gateway
- payment_gateway (string) e.g., "stripe", "paypal"
- payment_method (string) e.g., "card", "bank_transfer"
- status (enum: 'pending', 'authorized', 'captured', 'failed', 'refunded', 'cancelled')
- amount (decimal)
- currency (string)
- gateway_response (json)
- error_message (text, nullable)
- processed_at (datetime, nullable)
- metadata (json, nullable)
- timestamps
```

4. **inventory_reservations**
```sql
- id
- checkout_session_id (foreign key)
- stock_id (foreign key)
- product_id (foreign key)
- warehouse_id (foreign key)
- quantity_reserved (decimal)
- status (enum: 'active', 'released', 'fulfilled', 'expired')
- expires_at (datetime)
- released_at (datetime, nullable)
- fulfilled_at (datetime, nullable)
- notes (text, nullable)
- timestamps
```

#### 1.2 Model Creation

**Models to Create:**
1. ShippingMethod.php
2. CheckoutSession.php
3. PaymentTransaction.php
4. InventoryReservation.php

**Key Relationships:**
- CheckoutSession hasOne ShoppingCart
- CheckoutSession belongsTo ShippingMethod
- CheckoutSession hasMany PaymentTransaction
- CheckoutSession hasMany InventoryReservation
- CheckoutSession belongsTo User (nullable for guest checkout)
- PaymentTransaction belongsTo CheckoutSession
- PaymentTransaction belongsTo SalesOrder (after conversion)
- InventoryReservation belongsTo CheckoutSession
- InventoryReservation belongsTo Stock

#### 1.3 Service Layer

**CheckoutService.php** - Core business logic
- `initiateCheckout(ShoppingCart $cart, array $data): CheckoutSession`
- `updateShippingAddress(CheckoutSession $session, array $address): CheckoutSession`
- `selectShippingMethod(CheckoutSession $session, int $shippingMethodId): CheckoutSession`
- `calculateTotals(CheckoutSession $session): array`
- `validateInventoryAvailability(CheckoutSession $session): bool`
- `reserveInventory(CheckoutSession $session): array`
- `releaseInventory(CheckoutSession $session): void`
- `completeCheckout(CheckoutSession $session): SalesOrder`

**InventoryReservationService.php** - Inventory management
- `reserveItems(CheckoutSession $session): array`
- `releaseReservation(InventoryReservation $reservation): void`
- `fulfillReservation(InventoryReservation $reservation): void`
- `cleanupExpiredReservations(): int`

---

### Stage 2: Payment Gateway Integration (Day 1, 3-4 hours)

#### 2.1 Payment Abstraction Layer

**PaymentGatewayInterface.php**
```php
interface PaymentGatewayInterface
{
    public function createPaymentIntent(CheckoutSession $session): array;
    public function capturePayment(string $paymentIntentId): array;
    public function refundPayment(string $transactionId, float $amount): array;
    public function getPaymentStatus(string $paymentIntentId): string;
    public function handleWebhook(array $payload): void;
}
```

**Implementations:**
1. **StripePaymentGateway.php** (primary)
   - Integration with Stripe SDK
   - Payment Intent creation
   - Webhook handling
   - Card payment processing

2. **MockPaymentGateway.php** (testing)
   - Simulated payment for development/testing
   - No external API calls
   - Configurable success/failure

#### 2.2 PaymentService

**PaymentService.php**
- `processPayment(CheckoutSession $session, string $gateway, array $paymentData): PaymentTransaction`
- `verifyPayment(PaymentTransaction $transaction): bool`
- `refundPayment(PaymentTransaction $transaction, float $amount): PaymentTransaction`
- `handleWebhook(string $gateway, array $payload): void`

#### 2.3 Controllers

**CheckoutController.php** - Main checkout flow
- `POST /api/v1/checkout/initiate` - Start checkout from cart
- `PUT /api/v1/checkout/{session}/address` - Set shipping/billing address
- `PUT /api/v1/checkout/{session}/shipping` - Select shipping method
- `GET /api/v1/checkout/{session}/summary` - Get order summary
- `POST /api/v1/checkout/{session}/complete` - Finalize checkout

**PaymentController.php** - Payment handling
- `POST /api/v1/checkout/{session}/payment` - Process payment
- `GET /api/v1/checkout/{session}/payment-status` - Check payment status
- `POST /api/v1/webhooks/payment/{gateway}` - Payment gateway webhooks

**ShippingMethodController.php** - Shipping options
- `GET /api/v1/shipping-methods` - List available methods
- `GET /api/v1/shipping-methods/{id}/calculate` - Calculate shipping cost

---

### Stage 3: Order Tracking & Status (Day 2, 3-4 hours)

#### 3.1 Order Status Enhancement

**Extend SalesOrder model** (already exists in Sales module):
- Add `order_source` field (enum: 'ecommerce', 'manual', 'api')
- Add `checkout_session_id` field
- Add `tracking_number` field
- Add `tracking_url` field
- Add status transition methods

**OrderStatusService.php**
- `updateOrderStatus(SalesOrder $order, string $newStatus): void`
- `addStatusHistory(SalesOrder $order, string $status, string $notes): void`
- `getStatusHistory(SalesOrder $order): array`
- `canTransitionTo(SalesOrder $order, string $targetStatus): bool`

**Status Flow:**
```
draft → pending → confirmed → processing → shipped → delivered → completed
                                      ↓
                                  cancelled/refunded
```

#### 3.2 Order Tracking

**OrderTrackingController.php**
- `GET /api/v1/orders/{id}/tracking` - Get tracking info
- `GET /api/v1/orders/{id}/status-history` - Get status history
- `POST /api/v1/orders/{id}/status` - Update status (admin)

#### 3.3 Customer Order Portal

**CustomerOrderController.php**
- `GET /api/v1/my-orders` - List customer's orders
- `GET /api/v1/my-orders/{id}` - Get order details
- `GET /api/v1/my-orders/{id}/invoice` - Download invoice (PDF)
- `POST /api/v1/my-orders/{id}/cancel` - Cancel order (if allowed)

---

### Stage 4: Email Notifications (Day 2, 2-3 hours)

#### 4.1 Notification System

**OrderNotificationService.php**
- `sendOrderConfirmation(SalesOrder $order): void`
- `sendPaymentConfirmation(PaymentTransaction $transaction): void`
- `sendShippingNotification(SalesOrder $order): void`
- `sendOrderStatusUpdate(SalesOrder $order, string $newStatus): void`
- `sendOrderCancellation(SalesOrder $order): void`

#### 4.2 Email Templates (Blade views)

**resources/views/emails/orders/**
- order-confirmation.blade.php
- payment-confirmation.blade.php
- shipping-notification.blade.php
- order-status-update.blade.php
- order-cancellation.blade.php

#### 4.3 Queue Configuration

Use Laravel Queues for async email sending:
```php
dispatch(new SendOrderConfirmationEmail($order))->onQueue('emails');
```

---

### Stage 5: Integration & Testing (Day 3, 4-5 hours)

#### 5.1 Cart-to-Order Conversion

**OrderCreationService.php**
- `createOrderFromCheckout(CheckoutSession $session): SalesOrder`
- `linkFinanceEntities(SalesOrder $order): void` - Create AR Invoice
- `fulfillInventoryReservations(SalesOrder $order): void`
- `clearShoppingCart(ShoppingCart $cart): void`

**Integration Points:**
- Sales Module: Create SalesOrder with items
- Finance Module: Create ARInvoice
- Inventory Module: Fulfill reservations, create movements
- Accounting Module: Post journal entries

#### 5.2 Background Jobs

**Jobs to Create:**
1. `ProcessCheckoutJob` - Handle checkout completion
2. `CleanupExpiredCartsJob` - Remove old carts
3. `ReleaseExpiredReservationsJob` - Release inventory
4. `SendOrderNotificationJob` - Send emails

#### 5.3 Testing Strategy

**Unit Tests:**
- CheckoutService calculation logic
- Inventory reservation logic
- Payment gateway mocking
- Status transition validation

**Feature Tests:**
- Complete checkout flow (happy path)
- Payment failure handling
- Inventory insufficient handling
- Cart expiration
- Guest checkout vs authenticated

**Integration Tests:**
- Full e2e checkout with real data
- Cross-module integration (Sales, Finance, Inventory)
- Webhook handling
- Email sending

---

## API Endpoints Summary

### Checkout Flow (6 endpoints)
```
POST   /api/v1/checkout/initiate
PUT    /api/v1/checkout/{session}/address
PUT    /api/v1/checkout/{session}/shipping
GET    /api/v1/checkout/{session}/summary
POST   /api/v1/checkout/{session}/complete
GET    /api/v1/checkout/{session}
```

### Payment (3 endpoints)
```
POST   /api/v1/checkout/{session}/payment
GET    /api/v1/checkout/{session}/payment-status
POST   /api/v1/webhooks/payment/{gateway}
```

### Shipping (2 endpoints)
```
GET    /api/v1/shipping-methods
GET    /api/v1/shipping-methods/{id}/calculate
```

### Order Tracking (5 endpoints)
```
GET    /api/v1/orders/{id}/tracking
GET    /api/v1/orders/{id}/status-history
POST   /api/v1/orders/{id}/status (admin)
GET    /api/v1/my-orders
GET    /api/v1/my-orders/{id}
```

**Total New Endpoints:** ~16 endpoints

---

## Technical Architecture

### Service Layer Pattern
```
HTTP Request → Controller → Service → Model → Database
                              ↓
                      External APIs (Payment Gateway)
                              ↓
                         Queue Jobs
```

### State Machine (Checkout)
```
Initiated → Address → Shipping → Payment → Completed
     ↓                                          ↓
  Expired                                   Failed
```

### Inventory Reservation Flow
```
1. Customer starts checkout → Reserve inventory
2. Payment processing (15 min timeout)
   - Success → Fulfill reservation → Create order
   - Failure → Release reservation
   - Timeout → Release reservation (background job)
```

---

## Dependencies

### New Composer Packages
```bash
composer require stripe/stripe-php  # Stripe payment gateway
composer require guzzlehttp/guzzle  # HTTP client for external APIs (if not installed)
```

### Environment Configuration
```env
# Payment Gateways
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Email
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database  # or redis for production
```

---

## Success Criteria

| Criteria | Target | Measurement |
|----------|--------|-------------|
| Complete checkout flow working | ✅ | All 6 checkout endpoints functional |
| Payment integration (Stripe) | ✅ | Successful test payment processing |
| Inventory reservation | ✅ | Stock reserved during checkout |
| Order creation from cart | ✅ | SalesOrder created with items |
| Email notifications | ✅ | Order confirmation sent |
| Order tracking | ✅ | Customer can view order status |
| Response times < 2s | ✅ | Performance benchmark |
| Test coverage > 80% | ✅ | PHPUnit coverage report |

---

## Business Value

### 1. Complete E-commerce Solution
- End-to-end checkout experience
- Multiple payment methods support
- Automated order processing

### 2. Inventory Management
- Automatic stock reservation
- Prevents overselling
- Real-time availability

### 3. Customer Experience
- Order tracking and history
- Email notifications
- Guest checkout support

### 4. Financial Integration
- Automatic AR Invoice creation
- Payment reconciliation
- Accounting entries

---

## Risk Mitigation

### 1. Payment Gateway Issues
- **Risk:** Payment API downtime
- **Mitigation:** Queue-based processing, retry logic, fallback to manual processing

### 2. Inventory Conflicts
- **Risk:** Race conditions on stock
- **Mitigation:** Database transactions, pessimistic locking, reservation system

### 3. Failed Order Creation
- **Risk:** Payment captured but order not created
- **Mitigation:** Idempotency keys, transaction rollback, manual reconciliation dashboard

### 4. Email Delivery
- **Risk:** Emails not sent
- **Mitigation:** Queue-based sending, logging, retry mechanism

---

## Next Steps After Phase 4.1

### Phase 5.1: Billing/CFDI Module (5-7 days)
- Mexican electronic invoicing
- SAT compliance
- PAC integration

### Phase 4.3: Advanced Ecommerce (3-4 days)
- Product reviews and ratings
- Wishlist functionality
- Related products/recommendations
- Multi-currency support

### Phase 4.4: Loyalty & Promotions (2-3 days)
- Loyalty points system
- Advanced promotion engine
- Gift cards
- Subscription products

---

## Code Estimates

| Component | Estimated Lines |
|-----------|----------------|
| Migrations | 400 |
| Models | 500 |
| Services | 1,200 |
| Controllers | 800 |
| Tests | 1,500 |
| Jobs | 300 |
| **Total** | **~4,700 lines** |

---

**Status:** 📋 Planning Complete - Ready for Implementation
**Start Implementation:** Stage 1 - Checkout Flow Foundation
**Estimated Completion:** 2025-10-31 (2-3 days)
