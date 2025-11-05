# Inventory Module - Frontend Integration Guide

**Module:** Inventory
**Entities:** 5 (Warehouse, WarehouseLocation, Stock, ProductBatch, InventoryMovement)
**Endpoints:** 25
**Base Path:** `/api/v1`

## Overview

The Inventory module manages warehouse locations, stock levels, product batches, and inventory movements across multiple warehouses.

## Entities

### 1. Warehouse

**Endpoint:** `/warehouses`
**Resource Type:** `warehouses`

#### TypeScript Interface

```typescript
interface Warehouse {
  id: string;
  name: string;
  code: string;
  address: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  postalCode: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `code` | `code` | string | Yes | Yes | Yes |
| `address` | `address` | string | No | No | No |
| `city` | `city` | string | No | No | Yes |
| `state` | `state` | string | No | No | Yes |
| `country` | `country` | string | No | No | Yes |
| `postalCode` | `postal_code` | string | No | No | No |
| `isActive` | `is_active` | boolean | No | No | Yes |

---

### 2. WarehouseLocation

**Endpoint:** `/warehouse-locations`
**Resource Type:** `warehouse-locations`

#### TypeScript Interface

```typescript
interface WarehouseLocation {
  id: string;
  warehouseId: number;
  aisle: string | null;
  rack: string | null;
  shelf: string | null;
  bin: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `warehouse` → Warehouse (belongsTo)

---

### 3. Stock

**Endpoint:** `/stocks`
**Resource Type:** `stocks`

#### TypeScript Interface

```typescript
interface Stock {
  id: string;
  productId: number;
  warehouseId: number;
  quantity: number;
  reservedQuantity: number;
  availableQuantity: number; // Calculated: quantity - reservedQuantity
  minimumQuantity: number;
  maximumQuantity: number;
  reorderPoint: number;
  locationId: number | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Calculated Fields

- **availableQuantity**: Automatically calculated as `quantity - reservedQuantity`

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)
- `location` → WarehouseLocation (belongsTo)

#### Example: Check Available Stock

```javascript
const response = await fetch(
  '/api/v1/stocks?filter[productId]=123&filter[warehouseId]=1&include=product,warehouse',
  { headers }
);

const stock = await response.json();
console.log(stock.data[0].attributes);
// {
//   quantity: 100,
//   reservedQuantity: 20,
//   availableQuantity: 80, // Calculated field
//   minimumQuantity: 10,
//   reorderPoint: 15
// }
```

---

### 4. ProductBatch

**Endpoint:** `/product-batches`
**Resource Type:** `product-batches`

#### TypeScript Interface

```typescript
interface ProductBatch {
  id: string;
  productId: number;
  warehouseId: number;
  batchNumber: string;
  quantity: number;
  expirationDate: string | null;
  manufacturingDate: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)

#### Example: Create Batch

```javascript
const payload = {
  data: {
    type: "product-batches",
    attributes: {
      productId: 123,
      warehouseId: 1,
      batchNumber: "BATCH-2025-001",
      quantity: 100,
      expirationDate: "2026-12-31",
      manufacturingDate: "2025-01-01"
    }
  }
};

const response = await fetch('/api/v1/product-batches', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

---

### 5. InventoryMovement

**Endpoint:** `/inventory-movements`
**Resource Type:** `inventory-movements`

#### TypeScript Interface

```typescript
type MovementType = 'entry' | 'exit' | 'adjustment' | 'transfer';

interface InventoryMovement {
  id: string;
  productId: number;
  warehouseId: number;
  type: MovementType;
  quantity: number;
  previousStock: number;
  newStock: number;
  reason: string | null;
  reference: string | null;
  userId: number;
  destinationWarehouseId: number | null;
  batchInfo: Record<string, any> | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Movement Types

- **entry**: Stock entering warehouse (purchases, returns, etc.)
- **exit**: Stock leaving warehouse (sales, transfers out, etc.)
- **adjustment**: Stock count adjustments (corrections, cycle counts)
- **transfer**: Stock transfer between warehouses

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)
- `user` → User (belongsTo)
- `destinationWarehouse` → Warehouse (belongsTo) - Only for transfers

#### Examples

**Stock Entry:**
```javascript
const payload = {
  data: {
    type: "inventory-movements",
    attributes: {
      productId: 123,
      warehouseId: 1,
      type: "entry",
      quantity: 50,
      reason: "Purchase Order #PO-001",
      reference: "PO-001",
      userId: 1,
      batchInfo: {
        batchNumber: "BATCH-2025-001",
        expirationDate: "2026-12-31"
      }
    }
  }
};
```

**Stock Transfer:**
```javascript
const payload = {
  data: {
    type: "inventory-movements",
    attributes: {
      productId: 123,
      warehouseId: 1,           // Source warehouse
      destinationWarehouseId: 2, // Destination warehouse
      type: "transfer",
      quantity: 20,
      reason: "Stock replenishment",
      userId: 1
    }
  }
};
```

---

## Common Use Cases

### 1. Check Stock Availability

```javascript
async function checkStockAvailability(productId, warehouseId) {
  const response = await fetch(
    `/api/v1/stocks?filter[productId]=${productId}&filter[warehouseId]=${warehouseId}`,
    { headers }
  );

  const data = await response.json();

  if (data.data.length === 0) {
    return { available: false, quantity: 0 };
  }

  const stock = data.data[0].attributes;
  return {
    available: stock.availableQuantity > 0,
    quantity: stock.availableQuantity,
    needsReorder: stock.quantity <= stock.reorderPoint
  };
}
```

### 2. Movement History for Product

```javascript
async function getMovementHistory(productId, warehouseId = null) {
  let url = `/api/v1/inventory-movements?filter[productId]=${productId}&include=user,warehouse&sort=-createdAt`;

  if (warehouseId) {
    url += `&filter[warehouseId]=${warehouseId}`;
  }

  const response = await fetch(url, { headers });
  return await response.json();
}
```

### 3. Low Stock Alert

```javascript
async function getLowStockProducts(warehouseId) {
  const response = await fetch(
    `/api/v1/stocks?filter[warehouseId]=${warehouseId}&include=product`,
    { headers }
  );

  const data = await response.json();

  return data.data.filter(stock => {
    const attrs = stock.attributes;
    return attrs.quantity <= attrs.reorderPoint;
  });
}
```

### 4. Transfer Stock Between Warehouses

```javascript
async function transferStock(productId, fromWarehouseId, toWarehouseId, quantity, reason) {
  // 1. Create exit movement from source
  const exitPayload = {
    data: {
      type: "inventory-movements",
      attributes: {
        productId,
        warehouseId: fromWarehouseId,
        destinationWarehouseId: toWarehouseId,
        type: "transfer",
        quantity,
        reason,
        userId: currentUser.id
      }
    }
  };

  const response = await fetch('/api/v1/inventory-movements', {
    method: 'POST',
    headers,
    body: JSON.stringify(exitPayload)
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
- `GET /api/v1/warehouses` - List warehouses
- `GET /api/v1/warehouse-locations` - List locations
- `GET /api/v1/stocks` - Check stock levels
- `GET /api/v1/product-batches` - List batches
- `GET /api/v1/inventory-movements` - Movement history

**Related Modules:**
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Process sales orders
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Receive purchase orders
