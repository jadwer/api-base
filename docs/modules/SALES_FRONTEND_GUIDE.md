# Sales Module - Frontend Integration Guide

**Module:** Sales
**Entities:** 3 (SalesOrder, SalesOrderItem, OrderTracking)
**Endpoints:** 15
**Base Path:** `/api/v1`

## Overview

The Sales module manages customer sales orders, order items, and order tracking. It integrates with the Contacts module for customer management, Finance module for AR invoice generation, and includes a comprehensive order tracking system.

## Entities

### 1. SalesOrder

**Endpoint:** `/sales-orders`
**Resource Type:** `sales-orders`

#### TypeScript Interface

```typescript
type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'completed' | 'cancelled' | 'returned' | 'refunded';

interface SalesOrder {
  id: string;
  contactId: number;
  orderNumber: string;
  status: OrderStatus;
  orderDate: string;
  approvedAt: string | null;
  deliveredAt: string | null;
  subtotalAmount: number;
  taxAmount: number;
  discountTotal: number;
  totalAmount: number;
  notes: string | null;

  // Finance integration fields
  arInvoiceId: number | null;
  invoicingStatus: string | null;
  invoicingNotes: string | null;

  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `contactId` | `contact_id` | number | Yes | No | Yes |
| `orderNumber` | `order_number` | string | Yes | Yes | Yes |
| `status` | `status` | string | Yes | Yes | Yes |
| `orderDate` | `order_date` | date | Yes | Yes | Yes |
| `approvedAt` | `approved_at` | datetime | No | Yes | No |
| `deliveredAt` | `delivered_at` | datetime | No | Yes | No |
| `subtotalAmount` | `subtotal_amount` | number | Yes | Yes | No |
| `taxAmount` | `tax_amount` | number | No | No | No |
| `discountTotal` | `discount_total` | number | No | No | No |
| `totalAmount` | `total_amount` | number | Yes | Yes | No |
| `notes` | `notes` | string | No | No | No |
| `arInvoiceId` | `ar_invoice_id` | number | No | No | Yes |
| `invoicingStatus` | `invoicing_status` | string | No | Yes | Yes |
| `metadata` | `metadata` | object | No | No | No |

#### Relationships

- `contact` → Contact (belongsTo) - The customer
- `customer` → Contact (belongsTo) - Alias for contact
- `items` → SalesOrderItem[] (hasMany)
- `arInvoice` → ARInvoice (belongsTo) - Generated AR invoice

#### Examples

**Create Sales Order:**
```javascript
const payload = {
  data: {
    type: "sales-orders",
    attributes: {
      contactId: 10,
      orderNumber: "SO-2025-001",
      orderDate: "2025-11-05",
      status: "pending",
      subtotalAmount: 1000.00,
      taxAmount: 160.00,
      discountTotal: 50.00,
      totalAmount: 1110.00,
      notes: "Priority delivery"
    }
  }
};

const response = await fetch('/api/v1/sales-orders', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

**List Orders with Customer:**
```javascript
const response = await fetch(
  '/api/v1/sales-orders?filter[status]=pending&include=customer,items&sort=-orderDate',
  { headers }
);
```

---

### 2. SalesOrderItem

**Endpoint:** `/sales-order-items`
**Resource Type:** `sales-order-items`

#### TypeScript Interface

```typescript
interface SalesOrderItem {
  id: string;
  salesOrderId: number;
  productId: number;
  quantity: number;
  unitPrice: number;
  discount: number;
  total: number;

  // Finance integration fields
  arInvoiceLineId: number | null;
  invoicedQuantity: number | null;
  invoicedAmount: number | null;

  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `salesOrderId` | `sales_order_id` | number | Yes | Yes | Yes |
| `productId` | `product_id` | number | Yes | Yes | Yes |
| `quantity` | `quantity` | number | Yes | Yes | Yes |
| `unitPrice` | `unit_price` | number | Yes | Yes | Yes |
| `discount` | `discount` | number | No | Yes | No |
| `total` | `total` | number | Yes | Yes | Yes |
| `arInvoiceLineId` | `ar_invoice_line_id` | number | No | No | No |
| `invoicedQuantity` | `invoiced_quantity` | number | No | Yes | No |
| `invoicedAmount` | `invoiced_amount` | number | No | Yes | No |
| `metadata` | `metadata` | object | No | No | No |

#### Relationships

- `salesOrder` → SalesOrder (belongsTo)
- `product` → Product (belongsTo)

---

### 3. OrderTracking

**Endpoints:** `/orders/{id}/tracking`, `/orders/{id}/status-history`
**Note:** These are custom REST endpoints, not JSON:API

#### TypeScript Interface

```typescript
interface OrderTracking {
  orderNumber: string;
  status: string;
  trackingNumber: string | null;
  trackingUrl: string | null;
  orderDate: string;
  estimatedDelivery: string | null;
  currentLocation: string | null;
  timeline: OrderTimelineEvent[];
}

interface OrderTimelineEvent {
  status: string;
  label: string;
  timestamp: string | null;
  completed: boolean;
}
```

#### Available Endpoints

**Get Order Tracking:**
```javascript
const response = await fetch('/api/v1/orders/123/tracking', { headers });

// Response:
{
  data: {
    orderNumber: "SO-2025-001",
    status: "shipped",
    trackingNumber: "1Z999AA1234567890",
    trackingUrl: "https://tracking.example.com/1Z999AA1234567890",
    orderDate: "2025-11-01",
    estimatedDelivery: "2025-11-08",
    currentLocation: "In transit",
    timeline: [
      { status: "placed", label: "Order Placed", timestamp: "2025-11-01", completed: true },
      { status: "confirmed", label: "Order Confirmed", timestamp: "2025-11-01", completed: true },
      { status: "processing", label: "Processing", timestamp: null, completed: true },
      { status: "shipped", label: "Shipped", timestamp: "2025-11-03", completed: true },
      { status: "delivered", label: "Delivered", timestamp: null, completed: false }
    ]
  }
}
```

**Get Status History:**
```javascript
const response = await fetch('/api/v1/orders/123/status-history', { headers });
```

**Update Order Status (Admin Only):**
```javascript
const payload = {
  status: "shipped",
  notes: "Package picked up by carrier",
  tracking_number: "1Z999AA1234567890",
  tracking_url: "https://tracking.example.com/1Z999AA1234567890"
};

const response = await fetch('/api/v1/orders/123/status', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

**Mark Order as Shipped (Admin Only):**
```javascript
const payload = {
  tracking_number: "1Z999AA1234567890",
  tracking_url: "https://tracking.example.com/1Z999AA1234567890",
  carrier: "UPS"
};

const response = await fetch('/api/v1/orders/123/ship', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

---

## Common Use Cases

### 1. Create Complete Sales Order

```javascript
async function createSalesOrder(orderData) {
  // 1. Calculate totals
  const subtotal = orderData.items.reduce((sum, item) =>
    sum + (item.quantity * item.unitPrice - (item.discount || 0)), 0
  );
  const tax = subtotal * 0.16; // 16% IVA
  const total = subtotal + tax;

  // 2. Create sales order
  const orderPayload = {
    data: {
      type: "sales-orders",
      attributes: {
        contactId: orderData.customerId,
        orderNumber: orderData.orderNumber,
        orderDate: new Date().toISOString().split('T')[0],
        status: "pending",
        subtotalAmount: subtotal,
        taxAmount: tax,
        discountTotal: orderData.discount || 0,
        totalAmount: total,
        notes: orderData.notes
      }
    }
  };

  const orderResponse = await fetch('/api/v1/sales-orders', {
    method: 'POST',
    headers,
    body: JSON.stringify(orderPayload)
  });

  const order = await orderResponse.json();
  const orderId = order.data.id;

  // 3. Create order items
  for (const item of orderData.items) {
    const itemPayload = {
      data: {
        type: "sales-order-items",
        attributes: {
          salesOrderId: parseInt(orderId),
          productId: item.productId,
          quantity: item.quantity,
          unitPrice: item.unitPrice,
          discount: item.discount || 0,
          total: (item.quantity * item.unitPrice) - (item.discount || 0)
        }
      }
    };

    await fetch('/api/v1/sales-order-items', {
      method: 'POST',
      headers,
      body: JSON.stringify(itemPayload)
    });
  }

  return order;
}
```

### 2. Order Tracking Widget

```javascript
async function OrderTrackingWidget({ orderId }) {
  const response = await fetch(`/api/v1/orders/${orderId}/tracking`, { headers });
  const { data: tracking } = await response.json();

  return (
    <div className="tracking-widget">
      <h3>Order {tracking.orderNumber}</h3>
      <p>Status: <span className={`status-${tracking.status}`}>{tracking.status}</span></p>

      {tracking.trackingNumber && (
        <p>Tracking: <a href={tracking.trackingUrl}>{tracking.trackingNumber}</a></p>
      )}

      {tracking.estimatedDelivery && (
        <p>Estimated Delivery: {tracking.estimatedDelivery}</p>
      )}

      <div className="timeline">
        {tracking.timeline.map(event => (
          <div key={event.status} className={event.completed ? 'completed' : 'pending'}>
            <strong>{event.label}</strong>
            {event.timestamp && <span>{event.timestamp}</span>}
          </div>
        ))}
      </div>
    </div>
  );
}
```

### 3. Customer Order History

```javascript
async function getCustomerOrders(customerId) {
  const response = await fetch(
    `/api/v1/sales-orders?filter[contact]=${customerId}&include=items.product&sort=-orderDate`,
    { headers }
  );

  return await response.json();
}
```

---

## Permissions

### Role-Based Access

| Role | Read | Create | Update | Delete | Tracking |
|------|------|--------|--------|--------|----------|
| **God** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Tech** | ✅ | ❌ | ❌ | ❌ | ✅ (read-only) |
| **Customer** | ✅ (own) | ✅ | ❌ | ❌ | ✅ (own) |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/sales-orders` - List all sales orders
- `POST /api/v1/sales-orders` - Create sales order
- `GET /api/v1/sales-orders/{id}` - Get single order
- `PATCH /api/v1/sales-orders/{id}` - Update order
- `DELETE /api/v1/sales-orders/{id}` - Delete order
- Same pattern for `/sales-order-items`
- `GET /api/v1/orders/{id}/tracking` - Get order tracking
- `GET /api/v1/orders/{id}/status-history` - Get status history
- `POST /api/v1/orders/{id}/status` - Update status (admin)
- `POST /api/v1/orders/{id}/ship` - Mark as shipped (admin)

**Related Modules:**
- [Contacts Module](CONTACTS_FRONTEND_GUIDE.md) - Customer management
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Inventory Module](INVENTORY_FRONTEND_GUIDE.md) - Stock management
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AR invoice generation
