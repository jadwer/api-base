# Sales Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| SalesOrder | `/api/v1/sales-orders` | Sales orders |
| SalesOrderItem | `/api/v1/sales-order-items` | Order line items |
| Shipment | `/api/v1/shipments` | Shipment tracking |
| Backorder | `/api/v1/backorders` | Backorder management |
| DiscountRule | `/api/v1/discount-rules` | Automatic discounts |

## Sales Order

```typescript
type OrderStatus = 'draft' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
type InvoicingStatus = 'pending' | 'partial' | 'invoiced' | 'not_required';

interface SalesOrder {
  id: string;
  contactId: number;
  orderNumber: string;       // Auto-generated: SO-XXXXXXXX
  status: OrderStatus;
  orderDate: string;
  approvedAt: string | null;
  deliveredAt: string | null;
  discountTotal: number;
  totalAmount: number;
  notes: string | null;

  // Finance integration
  arInvoiceId: number | null;
  invoicingStatus: InvoicingStatus;

  createdAt: string;
}

// List orders
GET /api/v1/sales-orders?filter[status]=confirmed&include=contact,items&sort=-orderDate

// Create order
POST /api/v1/sales-orders
{
  "data": {
    "type": "sales-orders",
    "attributes": {
      "contactId": 10,
      "orderNumber": "SO-2026-001",
      "orderDate": "2026-01-08",
      "status": "draft",
      "totalAmount": 1500.00,
      "notes": "Priority delivery"
    }
  }
}

// Update order
PATCH /api/v1/sales-orders/{id}

// Get order with items and customer
GET /api/v1/sales-orders/{id}?include=contact,items.product
```

### Status Flow

```
draft → confirmed → processing → shipped → delivered
          ↓              ↓
      cancelled      cancelled
```

### Filters

| Filter | Example |
|--------|---------|
| `filter[order_number]` | `?filter[order_number]=SO-2026-001` |
| `filter[status]` | `?filter[status]=processing` |
| `filter[contact_id]` | `?filter[contact_id]=10` |
| `filter[order_date]` | `?filter[order_date]=2026-01-08` |
| `filter[invoicing_status]` | `?filter[invoicing_status]=invoiced` |

## Sales Order Item

```typescript
interface SalesOrderItem {
  id: string;
  salesOrderId: number;
  productId: number;
  quantity: number;
  unitPrice: number;
  discount: number;          // Line discount amount
  total: number;             // quantity * unitPrice - discount

  // Finance integration
  arInvoiceLineId: number | null;
  invoicedQuantity: number | null;

  createdAt: string;
}

// Get items for order
GET /api/v1/sales-order-items?filter[sales_order_id]=1&include=product

// Create item
POST /api/v1/sales-order-items
{
  "data": {
    "type": "sales-order-items",
    "attributes": {
      "salesOrderId": 1,
      "productId": 5,
      "quantity": 10,
      "unitPrice": 150.00,
      "discount": 50.00,
      "total": 1450.00
    }
  }
}
```

## Order Tracking (Custom Endpoints)

```typescript
// Get tracking info
GET /api/v1/orders/{id}/tracking

// Response
{
  "data": {
    "orderNumber": "SO-2026-001",
    "status": "shipped",
    "trackingNumber": "1Z999AA1234567890",
    "trackingUrl": "https://tracking.example.com/...",
    "estimatedDelivery": "2026-01-15",
    "currentLocation": "In transit",
    "timeline": [
      { "status": "placed", "label": "Order Placed", "timestamp": "2026-01-08", "completed": true },
      { "status": "confirmed", "label": "Confirmed", "timestamp": "2026-01-08", "completed": true },
      { "status": "processing", "label": "Processing", "timestamp": "2026-01-09", "completed": true },
      { "status": "shipped", "label": "Shipped", "timestamp": "2026-01-10", "completed": true },
      { "status": "delivered", "label": "Delivered", "timestamp": null, "completed": false }
    ]
  }
}

// Update status (admin)
POST /api/v1/orders/{id}/status
{
  "status": "shipped",
  "notes": "Shipped via UPS",
  "tracking_number": "1Z999AA1234567890"
}

// Mark as shipped (admin)
POST /api/v1/orders/{id}/ship
{
  "tracking_number": "1Z999AA1234567890",
  "tracking_url": "https://tracking.example.com/...",
  "carrier": "UPS"
}

// Get status history
GET /api/v1/orders/{id}/status-history
```

## Customer Portal (My Orders)

```typescript
// List my orders (customer only)
GET /api/v1/my-orders

// Get my order details
GET /api/v1/my-orders/{id}

// Cancel my order (draft/confirmed only)
POST /api/v1/my-orders/{id}/cancel

// Request return (delivered only)
POST /api/v1/my-orders/{id}/return
{
  "reason": "Product damaged",
  "items": [
    { "itemId": 1, "quantity": 1, "reason": "Defective" }
  ]
}

// Download invoice
GET /api/v1/my-orders/{id}/invoice
```

## Backorders

```typescript
type BackorderStatus = 'pending' | 'partial' | 'fulfilled' | 'cancelled';

interface Backorder {
  id: string;
  salesOrderId: number;
  salesOrderItemId: number;
  productId: number;
  quantityOrdered: number;
  quantityFulfilled: number;
  quantityPending: number;
  status: BackorderStatus;
  expectedDate: string | null;
  notes: string | null;
}

// Get pending backorders
GET /api/v1/backorders?filter[status]=pending

// Get backorders for product
GET /api/v1/backorders/pending-for-product/{productId}

// Fulfill backorder
POST /api/v1/backorders/{id}/fulfill
{
  "quantity": 5,
  "notes": "Partial fulfillment"
}

// Cancel backorder
POST /api/v1/backorders/{id}/cancel

// Auto-fulfill when stock arrives
POST /api/v1/backorders/fulfill-for-product
{
  "product_id": 1,
  "quantity_available": 100
}
```

## Discount Rules (Automatic)

```typescript
type DiscountType = 'percentage' | 'fixed_amount' | 'buy_x_get_y';

interface DiscountRule {
  id: string;
  name: string;
  discountType: DiscountType;
  discountValue: number;
  minQuantity: number | null;
  minAmount: number | null;
  productId: number | null;      // Specific product
  categoryId: number | null;     // Or category
  contactId: number | null;      // Or specific customer
  startDate: string | null;
  endDate: string | null;
  isActive: boolean;
  priority: number;
}

// Get active discount rules
GET /api/v1/discount-rules?filter[is_active]=true

// Apply discounts to order (backend calculates)
POST /api/v1/sales-orders/{id}/apply-discounts

// Response includes applied discounts
{
  "discountsApplied": [
    { "rule": "Volume Discount", "amount": 150.00 },
    { "rule": "Customer Loyalty", "amount": 50.00 }
  ],
  "totalDiscount": 200.00,
  "newTotal": 1300.00
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Credit Check | Validates customer credit before confirm | Show credit status |
| Stock Reservation | Confirming order reserves inventory | Check availability first |
| Auto Invoice | Completing order creates AR Invoice | Navigate to invoice |
| Status Transitions | Only valid transitions allowed | Disable invalid buttons |
| Backorder Creation | If stock insufficient, creates backorder | Show backorder notification |
| Discount Application | Rules applied automatically | Show discount breakdown |

## Complete Order Workflow

```typescript
async function createCompleteOrder(customerId: number, items: OrderItem[]) {
  // 1. Validate credit
  const creditCheck = await fetch(`/api/v1/contacts/${customerId}/credit-status`, { headers });
  const credit = await creditCheck.json();

  const orderTotal = items.reduce((sum, item) => sum + item.total, 0);
  if (credit.availableCredit < orderTotal) {
    throw new Error(`Insufficient credit. Available: $${credit.availableCredit}`);
  }

  // 2. Check stock availability
  for (const item of items) {
    const stock = await checkAvailability(item.productId, item.warehouseId, item.quantity);
    if (!stock.available) {
      throw new Error(`Insufficient stock for product ${item.productId}`);
    }
  }

  // 3. Create order header
  const orderResponse = await fetch('/api/v1/sales-orders', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'sales-orders',
        attributes: {
          contactId: customerId,
          orderNumber: generateOrderNumber(),
          orderDate: new Date().toISOString().split('T')[0],
          status: 'draft',
          totalAmount: orderTotal
        }
      }
    })
  });
  const order = await orderResponse.json();

  // 4. Create order items
  for (const item of items) {
    await fetch('/api/v1/sales-order-items', {
      method: 'POST',
      headers,
      body: JSON.stringify({
        data: {
          type: 'sales-order-items',
          attributes: {
            salesOrderId: parseInt(order.data.id),
            productId: item.productId,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            discount: item.discount || 0,
            total: item.total
          }
        }
      })
    });
  }

  // 5. Apply automatic discounts
  await fetch(`/api/v1/sales-orders/${order.data.id}/apply-discounts`, {
    method: 'POST',
    headers
  });

  // 6. Confirm order (reserves stock)
  await fetch(`/api/v1/orders/${order.data.id}/status`, {
    method: 'POST',
    headers,
    body: JSON.stringify({ status: 'confirmed' })
  });

  return order;
}
```
