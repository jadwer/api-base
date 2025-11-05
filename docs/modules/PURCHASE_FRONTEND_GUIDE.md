# Purchase Module - Frontend Integration Guide

**Module:** Purchase
**Entities:** 2 (PurchaseOrder, PurchaseOrderItem)
**Endpoints:** 10
**Base Path:** `/api/v1`

## Overview

The Purchase module manages procurement operations including purchase orders and their line items. It integrates with the Contacts module for supplier management and Finance module for AP invoice generation.

## Entities

### 1. PurchaseOrder

**Endpoint:** `/purchase-orders`
**Resource Type:** `purchase-orders`

#### TypeScript Interface

```typescript
type PurchaseOrderStatus = 'draft' | 'pending' | 'approved' | 'received' | 'cancelled';

interface PurchaseOrder {
  id: string;
  contactId: number;
  orderDate: string;
  status: PurchaseOrderStatus;
  totalAmount: number;
  notes: string | null;

  // Finance integration fields
  apInvoiceId: number | null;
  invoicingStatus: string | null;
  invoicingNotes: string | null;

  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `contactId` | `contact_id` | number | Yes | No | Yes |
| `orderDate` | `order_date` | date | Yes | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `totalAmount` | `total_amount` | number | Yes | Yes | No |
| `notes` | `notes` | string | No | No | No |
| `apInvoiceId` | `ap_invoice_id` | number | No | Yes | No |
| `invoicingStatus` | `invoicing_status` | string | No | Yes | No |
| `invoicingNotes` | `invoicing_notes` | string | No | No | No |
| `createdAt` | `created_at` | datetime | Auto | Yes | No |
| `updatedAt` | `updated_at` | datetime | Auto | Yes | No |

#### Relationships

- `contact` → Contact (belongsTo) - The supplier
- `purchaseOrderItems` → PurchaseOrderItem[] (hasMany)
- `apInvoice` → APInvoice (belongsTo) - Generated AP invoice

#### Examples

**Create Purchase Order:**
```javascript
const payload = {
  data: {
    type: "purchase-orders",
    attributes: {
      contactId: 5,
      orderDate: "2025-11-05",
      status: "draft",
      totalAmount: 5000.00,
      notes: "Quarterly office supplies"
    }
  }
};

const response = await fetch('/api/v1/purchase-orders', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

**List Orders with Supplier:**
```javascript
const response = await fetch(
  '/api/v1/purchase-orders?filter[status]=pending&include=contact,purchaseOrderItems&sort=-orderDate',
  { headers }
);
```

---

### 2. PurchaseOrderItem

**Endpoint:** `/purchase-order-items`
**Resource Type:** `purchase-order-items`

#### TypeScript Interface

```typescript
interface PurchaseOrderItem {
  id: string;
  purchaseOrderId: number;
  productId: number;
  quantity: number;
  unitPrice: number;
  discount: number;
  subtotal: number;
  total: number;
  metadata: Record<string, any> | null;

  // Finance integration fields
  apInvoiceLineId: number | null;
  invoicedQuantity: number | null;
  invoicedAmount: number | null;

  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `purchaseOrderId` | `purchase_order_id` | number | Yes | Yes | Yes |
| `productId` | `product_id` | number | Yes | Yes | Yes |
| `quantity` | `quantity` | number | Yes | Yes | Yes |
| `unitPrice` | `unit_price` | number | Yes | Yes | Yes |
| `discount` | `discount` | number | No | Yes | Yes |
| `subtotal` | `subtotal` | number | Yes | Yes | Yes |
| `total` | `total` | number | Yes | Yes | Yes |
| `metadata` | `metadata` | object | No | No | No |
| `apInvoiceLineId` | `ap_invoice_line_id` | number | No | Yes | No |
| `invoicedQuantity` | `invoiced_quantity` | number | No | Yes | No |
| `invoicedAmount` | `invoiced_amount` | number | No | Yes | No |

#### Relationships

- `purchaseOrder` → PurchaseOrder (belongsTo)
- `product` → Product (belongsTo)

---

## Common Use Cases

### 1. Create Complete Purchase Order

```javascript
async function createPurchaseOrder(orderData) {
  // 1. Create purchase order
  const orderPayload = {
    data: {
      type: "purchase-orders",
      attributes: {
        contactId: orderData.supplierId,
        orderDate: orderData.orderDate,
        status: "draft",
        totalAmount: orderData.items.reduce((sum, item) => sum + item.total, 0),
        notes: orderData.notes
      }
    }
  };

  const orderResponse = await fetch('/api/v1/purchase-orders', {
    method: 'POST',
    headers,
    body: JSON.stringify(orderPayload)
  });

  const order = await orderResponse.json();
  const orderId = order.data.id;

  // 2. Create order items
  for (const item of orderData.items) {
    const itemPayload = {
      data: {
        type: "purchase-order-items",
        attributes: {
          purchaseOrderId: parseInt(orderId),
          productId: item.productId,
          quantity: item.quantity,
          unitPrice: item.unitPrice,
          discount: item.discount || 0,
          subtotal: item.quantity * item.unitPrice,
          total: (item.quantity * item.unitPrice) - (item.discount || 0)
        }
      }
    };

    await fetch('/api/v1/purchase-order-items', {
      method: 'POST',
      headers,
      body: JSON.stringify(itemPayload)
    });
  }

  return order;
}
```

### 2. Track Purchase Order Status

```javascript
async function getPurchaseOrderWithDetails(orderId) {
  const response = await fetch(
    `/api/v1/purchase-orders/${orderId}?include=contact,purchaseOrderItems.product`,
    { headers }
  );

  const data = await response.json();

  return {
    order: data.data.attributes,
    supplier: data.included.find(inc => inc.type === 'contacts'),
    items: data.included.filter(inc => inc.type === 'purchase-order-items')
  };
}
```

### 3. Update Order Status

```javascript
async function updateOrderStatus(orderId, newStatus) {
  const payload = {
    data: {
      type: "purchase-orders",
      id: orderId,
      attributes: {
        status: newStatus
      }
    }
  };

  const response = await fetch(`/api/v1/purchase-orders/${orderId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

---

## Permissions

### Role-Based Access

| Role | Read | Create | Update | Delete |
|------|------|--------|--------|--------|
| **God** | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ | ✅ | ✅ |
| **Tech** | ✅ | ❌ | ❌ | ❌ |
| **Customer** | ❌ | ❌ | ❌ | ❌ |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/purchase-orders` - List all purchase orders
- `POST /api/v1/purchase-orders` - Create purchase order
- `GET /api/v1/purchase-orders/{id}` - Get single order
- `PATCH /api/v1/purchase-orders/{id}` - Update order
- `DELETE /api/v1/purchase-orders/{id}` - Delete order
- Same pattern for `/purchase-order-items`

**Related Modules:**
- [Contacts Module](CONTACTS_FRONTEND_GUIDE.md) - Supplier management
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Inventory Module](INVENTORY_FRONTEND_GUIDE.md) - Receive inventory
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AP invoice generation
