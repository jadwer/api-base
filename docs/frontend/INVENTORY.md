# Inventory Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Warehouse | `/api/v1/warehouses` | Storage locations |
| WarehouseLocation | `/api/v1/warehouse-locations` | Aisle/Rack/Shelf |
| Stock | `/api/v1/stocks` | Current inventory levels |
| InventoryMovement | `/api/v1/inventory-movements` | Stock transactions |
| ProductBatch | `/api/v1/product-batches` | Lot/batch tracking |
| CycleCount | `/api/v1/cycle-counts` | Inventory audits |

## Warehouse

```typescript
interface Warehouse {
  id: string;
  code: string;          // WH-001
  name: string;
  address: string | null;
  isActive: boolean;
  createdAt: string;
}

// List warehouses
GET /api/v1/warehouses?filter[is_active]=true

// Get warehouse with locations
GET /api/v1/warehouses/{id}?include=locations,stocks
```

## Stock

```typescript
interface Stock {
  id: string;
  productId: number;
  warehouseId: number;
  warehouseLocationId: number | null;
  quantity: number;
  reservedQuantity: number;
  availableQuantity: number;  // quantity - reservedQuantity (calculated)
  reorderPoint: number;
  maxQuantity: number;
  createdAt: string;
}

// Get stock by product
GET /api/v1/stocks?filter[product_id]=1&include=product,warehouse

// Get low stock items
GET /api/v1/stocks?filter[below_reorder]=true
```

### Stock Availability
```typescript
// Available = Quantity - Reserved
// Always check availableQuantity before creating orders
const canFulfill = stock.availableQuantity >= requestedQuantity;
```

## Inventory Movement

```typescript
type MovementType = 'entry' | 'exit' | 'transfer' | 'adjustment';

interface InventoryMovement {
  id: string;
  productId: number;
  warehouseId: number;
  warehouseLocationId: number | null;
  movementType: MovementType;
  quantity: number;
  previousStock: number;
  newStock: number;
  reference: string | null;
  notes: string | null;
  createdAt: string;
}

// List movements
GET /api/v1/inventory-movements?filter[product_id]=1&sort=-createdAt

// Create movement
POST /api/v1/inventory-movements
{
  "data": {
    "type": "inventory-movements",
    "attributes": {
      "productId": 1,
      "warehouseId": 1,
      "movementType": "entry",
      "quantity": 100,
      "reference": "PO-2026-001",
      "notes": "Purchase order receipt"
    }
  }
}
```

### Movement Types

| Type | Effect | Use Case |
|------|--------|----------|
| `entry` | + Stock | Purchase receipts, returns |
| `exit` | - Stock | Sales, consumption |
| `transfer` | Move between locations | Warehouse transfers |
| `adjustment` | Correct stock | Cycle counts, corrections |

## Product Batch (Lot Tracking)

```typescript
interface ProductBatch {
  id: string;
  productId: number;
  warehouseId: number;
  batchNumber: string;
  quantity: number;
  expirationDate: string | null;
  manufacturingDate: string | null;
  qualityStatus: 'pending' | 'passed' | 'failed';
  createdAt: string;
}

// Get batches for product (FEFO order)
GET /api/v1/product-batches?filter[product_id]=1&sort=expirationDate

// Get expiring batches
GET /api/v1/lot-traceability/expired
GET /api/v1/lot-traceability/expiring-soon?days=30
```

### FEFO (First Expired First Out)
```typescript
// Backend automatically selects batches with earliest expiration
// Frontend should display expiration dates for transparency
```

## Cycle Count

```typescript
type CountStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled';

interface CycleCount {
  id: string;
  productId: number;
  warehouseId: number;
  warehouseLocationId: number | null;
  scheduledDate: string;
  status: CountStatus;
  expectedQuantity: number;
  countedQuantity: number | null;
  variance: number | null;
  countedAt: string | null;
  countedById: number | null;
  notes: string | null;
}

// Get pending counts
GET /api/v1/cycle-counts?filter[status]=scheduled&sort=scheduledDate

// Update count result
PATCH /api/v1/cycle-counts/{id}
{
  "data": {
    "type": "cycle-counts",
    "id": "1",
    "attributes": {
      "status": "completed",
      "countedQuantity": 98,
      "notes": "2 units damaged"
    }
  }
}
```

## Inventory Reservation

```typescript
interface InventoryReservation {
  id: string;
  productId: number;
  warehouseId: number;
  quantity: number;
  sourceType: string;     // 'sales_order'
  sourceId: number;
  expiresAt: string | null;
  status: 'active' | 'fulfilled' | 'cancelled';
}

// Get reservations for product
GET /api/v1/inventory-reservations?filter[product_id]=1&filter[status]=active
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Negative Stock Prevention | Cannot create movements that result in negative stock | Validate before submit |
| FEFO Selection | Batches selected by earliest expiration | Show expiration dates |
| Reservation System | Confirmed orders reserve stock | Show available vs reserved |
| Quality Check | Only passed batches can be shipped | Filter by quality_status |
| Adjustment Approval | Adjustments may require approval | Check user permissions |
| Auto GL Posting | Movements create accounting entries | Transparent to frontend |

## Common Workflows

### Check Stock Before Order
```typescript
async function checkAvailability(productId: number, warehouseId: number, quantity: number) {
  const response = await fetch(
    `/api/v1/stocks?filter[product_id]=${productId}&filter[warehouse_id]=${warehouseId}`,
    { headers }
  );
  const { data } = await response.json();
  const stock = data[0];

  return {
    available: stock?.attributes.availableQuantity >= quantity,
    currentStock: stock?.attributes.quantity || 0,
    reserved: stock?.attributes.reservedQuantity || 0,
    available: stock?.attributes.availableQuantity || 0
  };
}
```

### Transfer Between Warehouses
```typescript
async function transferStock(productId: number, fromWarehouse: number, toWarehouse: number, quantity: number) {
  // Exit from source
  await fetch('/api/v1/inventory-movements', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'inventory-movements',
        attributes: {
          productId,
          warehouseId: fromWarehouse,
          movementType: 'transfer',
          quantity: -quantity,
          reference: `TRF-${Date.now()}`
        }
      }
    })
  });

  // Entry to destination
  await fetch('/api/v1/inventory-movements', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'inventory-movements',
        attributes: {
          productId,
          warehouseId: toWarehouse,
          movementType: 'transfer',
          quantity: quantity,
          reference: `TRF-${Date.now()}`
        }
      }
    })
  });
}
```
