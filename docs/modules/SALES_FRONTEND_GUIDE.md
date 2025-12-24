# Sales Module - Frontend Integration Guide

**Module:** Sales
**Entities:** 2 (SalesOrder, SalesOrderItem)
**Endpoints:** 24+ (CRUD + Custom tracking + Customer portal)
**Base Path:** `/api/v1`

## Overview

The Sales module manages customer sales orders, order items, and order tracking. It integrates with the Contacts module for customer management, Finance module for AR invoice generation, and includes a comprehensive order tracking system with customer portal.

## Entities

### 1. SalesOrder

**Endpoint:** `/sales-orders`
**Resource Type:** `sales-orders`

#### TypeScript Interface

```typescript
type OrderStatus = 'draft' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
type InvoicingStatus = 'pending' | 'partial' | 'invoiced' | 'not_required';
type FinancialStatus = 'pending' | 'partial' | 'paid' | 'overdue';

interface SalesOrder {
  id: string;
  contactId: number;
  orderNumber: string;
  status: OrderStatus;
  orderDate: string;
  approvedAt: string | null;
  deliveredAt: string | null;
  discountTotal: number;
  totalAmount: number;
  notes: string | null;

  // Finance integration fields
  arInvoiceId: number | null;
  invoicingStatus: InvoicingStatus;
  invoicingNotes: string | null;

  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}

interface SalesOrderCreateRequest {
  contactId: number;
  orderNumber: string;
  orderDate: string;
  status?: OrderStatus;
  discountTotal?: number;
  totalAmount: number;
  notes?: string;
  metadata?: Record<string, any>;
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
| `discountTotal` | `discount_total` | number | No | No | No |
| `totalAmount` | `total_amount` | number | Yes | Yes | No |
| `notes` | `notes` | string | No | No | No |
| `arInvoiceId` | `ar_invoice_id` | number | No | No | Yes |
| `invoicingStatus` | `invoicing_status` | string | No | Yes | Yes |
| `invoicingNotes` | `invoicing_notes` | string | No | No | No |
| `metadata` | `metadata` | object | No | No | No |

#### Relationships

- `contact` → Contact (belongsTo) - The customer
- `customer` → Contact (belongsTo) - Alias for contact
- `items` → SalesOrderItem[] (hasMany)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[order_number]` | `SO-2025-001` | Filter by order number |
| `filter[status]` | `pending` | Filter by status |
| `filter[contact]` | `10` | Filter by customer (contact_id) |
| `filter[order_date]` | `2025-01-15` | Filter by order date |
| `filter[invoicing_status]` | `invoiced` | Filter by invoicing status |
| `filter[ar_invoice_id]` | `5` | Filter by AR invoice |

#### Include Paths

- `contact` - Include customer details
- `customer` - Alias for contact
- `items` - Include order items
- `items.product` - Include items with product details

#### Examples

**Create Sales Order:**
```typescript
const payload = {
  data: {
    type: "sales-orders",
    attributes: {
      contactId: 10,
      orderNumber: "SO-2025-001",
      orderDate: "2025-11-05",
      status: "draft",
      discountTotal: 50.00,
      totalAmount: 1110.00,
      notes: "Priority delivery"
    }
  }
};

const response = await fetch('/api/v1/sales-orders', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/vnd.api+json',
    'Accept': 'application/vnd.api+json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(payload)
});
```

**List Orders with Customer:**
```typescript
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

interface SalesOrderItemCreateRequest {
  salesOrderId: number;
  productId: number;
  quantity: number;
  unitPrice: number;
  discount?: number;
  total: number;
  metadata?: Record<string, any>;
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

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[salesOrderId]` | `5` | Filter by sales order |
| `filter[productId]` | `123` | Filter by product |
| `filter[quantity]` | `10` | Filter by quantity |
| `filter[unitPrice]` | `99.99` | Filter by unit price |
| `filter[total]` | `999.90` | Filter by total |

#### Include Paths

- `salesOrder` - Include parent order
- `salesOrder.customer` - Include order with customer
- `product` - Include product details

---

## Order Tracking System

These are custom REST endpoints, not JSON:API.

### TypeScript Interfaces

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

interface StatusHistoryEntry {
  status: string;
  changedAt: string;
  changedBy: number;
  notes: string | null;
}
```

### Available Endpoints

#### Get Order Tracking

```http
GET /api/v1/orders/{orderId}/tracking
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "orderNumber": "SO-2025-001",
    "status": "shipped",
    "trackingNumber": "1Z999AA1234567890",
    "trackingUrl": "https://tracking.example.com/1Z999AA1234567890",
    "orderDate": "2025-11-01",
    "estimatedDelivery": "2025-11-08",
    "currentLocation": "In transit",
    "timeline": [
      { "status": "placed", "label": "Order Placed", "timestamp": "2025-11-01", "completed": true },
      { "status": "confirmed", "label": "Order Confirmed", "timestamp": "2025-11-01", "completed": true },
      { "status": "processing", "label": "Processing", "timestamp": null, "completed": true },
      { "status": "shipped", "label": "Shipped", "timestamp": "2025-11-03", "completed": true },
      { "status": "delivered", "label": "Delivered", "timestamp": null, "completed": false }
    ]
  }
}
```

#### Get Status History

```http
GET /api/v1/orders/{orderId}/status-history
Authorization: Bearer {token}
```

#### Update Order Status (Admin Only)

```http
POST /api/v1/orders/{orderId}/status
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "status": "shipped",
  "notes": "Package picked up by carrier",
  "tracking_number": "1Z999AA1234567890",
  "tracking_url": "https://tracking.example.com/1Z999AA1234567890"
}
```

#### Mark Order as Shipped (Admin Only)

```http
POST /api/v1/orders/{orderId}/ship
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "tracking_number": "1Z999AA1234567890",
  "tracking_url": "https://tracking.example.com/1Z999AA1234567890",
  "carrier": "UPS"
}
```

---

## Customer Order Portal

Custom endpoints for customer self-service.

### Available Endpoints

#### List My Orders

```http
GET /api/v1/my-orders
Authorization: Bearer {token}
```

Lists orders for the authenticated customer.

#### Get My Order Details

```http
GET /api/v1/my-orders/{orderId}
Authorization: Bearer {token}
```

#### Cancel My Order

```http
POST /api/v1/my-orders/{orderId}/cancel
Authorization: Bearer {token}
```

Only works for orders in `draft` or `confirmed` status.

#### Request Return

```http
POST /api/v1/my-orders/{orderId}/return
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "reason": "Product damaged",
  "items": [
    { "itemId": 1, "quantity": 1, "reason": "Defective" }
  ]
}
```

#### Download Invoice

```http
GET /api/v1/my-orders/{orderId}/invoice
Authorization: Bearer {token}
```

Returns PDF invoice if available.

---

## Common Use Cases

### 1. Create Complete Sales Order

```typescript
async function createSalesOrder(orderData: {
  customerId: number;
  orderNumber: string;
  items: Array<{
    productId: number;
    quantity: number;
    unitPrice: number;
    discount?: number;
  }>;
  discount?: number;
  notes?: string;
}) {
  // 1. Calculate totals
  const itemsTotal = orderData.items.reduce((sum, item) => {
    const itemTotal = (item.quantity * item.unitPrice) - (item.discount || 0);
    return sum + itemTotal;
  }, 0);

  const total = itemsTotal - (orderData.discount || 0);

  // 2. Create sales order
  const orderPayload = {
    data: {
      type: "sales-orders",
      attributes: {
        contactId: orderData.customerId,
        orderNumber: orderData.orderNumber,
        orderDate: new Date().toISOString().split('T')[0],
        status: "draft",
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
    const itemTotal = (item.quantity * item.unitPrice) - (item.discount || 0);

    const itemPayload = {
      data: {
        type: "sales-order-items",
        attributes: {
          salesOrderId: parseInt(orderId),
          productId: item.productId,
          quantity: item.quantity,
          unitPrice: item.unitPrice,
          discount: item.discount || 0,
          total: itemTotal
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

```typescript
async function getOrderTracking(orderId: number): Promise<OrderTracking> {
  const response = await fetch(`/api/v1/orders/${orderId}/tracking`, { headers });
  const { data } = await response.json();
  return data;
}

// React component example
function OrderTrackingWidget({ orderId }: { orderId: number }) {
  const [tracking, setTracking] = useState<OrderTracking | null>(null);

  useEffect(() => {
    getOrderTracking(orderId).then(setTracking);
  }, [orderId]);

  if (!tracking) return <div>Loading...</div>;

  return (
    <div className="tracking-widget">
      <h3>Order {tracking.orderNumber}</h3>
      <p>Status: <span className={`status-${tracking.status}`}>{tracking.status}</span></p>

      {tracking.trackingNumber && (
        <p>Tracking: <a href={tracking.trackingUrl!}>{tracking.trackingNumber}</a></p>
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

```typescript
async function getCustomerOrders(customerId: number) {
  const response = await fetch(
    `/api/v1/sales-orders?filter[contact]=${customerId}&include=items.product&sort=-orderDate`,
    { headers }
  );

  return response.json();
}
```

### 4. Ship Order with Tracking

```typescript
async function shipOrder(
  orderId: number,
  trackingInfo: {
    trackingNumber: string;
    trackingUrl?: string;
    carrier: string;
  }
) {
  const response = await fetch(`/api/v1/orders/${orderId}/ship`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      tracking_number: trackingInfo.trackingNumber,
      tracking_url: trackingInfo.trackingUrl,
      carrier: trackingInfo.carrier
    })
  });

  return response.json();
}
```

### 5. Update Order Status

```typescript
async function updateOrderStatus(
  orderId: number,
  status: OrderStatus,
  notes?: string
) {
  const response = await fetch(`/api/v1/orders/${orderId}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ status, notes })
  });

  return response.json();
}
```

---

## Order Status Flow

```
draft → confirmed → processing → shipped → delivered
                              ↓
                          cancelled (from draft/confirmed/processing)

delivered → returned → refunded
```

### Valid Status Transitions

| From | To |
|------|----|
| `draft` | `confirmed`, `cancelled` |
| `confirmed` | `processing`, `cancelled` |
| `processing` | `shipped`, `cancelled` |
| `shipped` | `delivered`, `returned` |
| `delivered` | `returned`, `completed` |
| `returned` | `refunded` |

---

## Permissions

### Role-Based Access

| Role | Read | Create | Update | Delete | Tracking | Ship |
|------|------|--------|--------|--------|----------|------|
| **God** | All | Yes | Yes | Yes | Yes | Yes |
| **Admin** | All | Yes | Yes | Yes | Yes | Yes |
| **Tech** | All | No | No | No | Read | No |
| **Customer** | Own | Yes | Limited | No | Own | No |

### Permission Names

| Entity | index | show | store | update | destroy |
|--------|-------|------|-------|--------|---------|
| sales-orders | `sales-orders.index` | `sales-orders.show` | `sales-orders.store` | `sales-orders.update` | `sales-orders.destroy` |
| sales-order-items | `sales-order-items.index` | `sales-order-items.show` | `sales-order-items.store` | `sales-order-items.update` | `sales-order-items.destroy` |

---

## Quick Reference

**JSON:API Endpoints:**
- `GET /api/v1/sales-orders` - List all sales orders
- `POST /api/v1/sales-orders` - Create sales order
- `GET /api/v1/sales-orders/{id}` - Get single order
- `PATCH /api/v1/sales-orders/{id}` - Update order
- `DELETE /api/v1/sales-orders/{id}` - Delete order
- Same pattern for `/sales-order-items`

**Order Tracking Endpoints:**
- `GET /api/v1/orders/{id}/tracking` - Get order tracking
- `GET /api/v1/orders/{id}/status-history` - Get status history
- `POST /api/v1/orders/{id}/status` - Update status (admin)
- `POST /api/v1/orders/{id}/ship` - Mark as shipped (admin)

**Customer Portal Endpoints:**
- `GET /api/v1/my-orders` - List customer's orders
- `GET /api/v1/my-orders/{id}` - Get customer's order
- `POST /api/v1/my-orders/{id}/cancel` - Cancel order
- `POST /api/v1/my-orders/{id}/return` - Request return
- `GET /api/v1/my-orders/{id}/invoice` - Download invoice

**Reporting Endpoints:**
- `GET /api/v1/sales-orders/reports` - Sales reports
- `GET /api/v1/sales-orders/customers` - Customer summary

**Related Modules:**
- [Contacts Module](CONTACTS_FRONTEND_GUIDE.md) - Customer management
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Inventory Module](INVENTORY_FRONTEND_GUIDE.md) - Stock management
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AR invoice generation
