# Inventory Module - Frontend Integration Guide

**Module:** Inventory
**Entities:** 5 (Warehouse, WarehouseLocation, Stock, ProductBatch, InventoryMovement)
**Endpoints:** 25
**Base Path:** `/api/v1`

## Overview

The Inventory module manages warehouse locations, stock levels, product batches, and inventory movements across multiple warehouses. Integrates with the Accounting module for GL posting on inventory movements.

## Entities

### 1. Warehouse

**Endpoint:** `/warehouses`
**Resource Type:** `warehouses`

#### TypeScript Interface

```typescript
type WarehouseType = 'main' | 'distribution' | 'retail' | 'storage' | 'cross-dock';

interface Warehouse {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  code: string;
  warehouseType: WarehouseType;
  address: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  postalCode: string | null;
  phone: string | null;
  email: string | null;
  managerName: string | null;
  maxCapacity: number | null;
  capacityUnit: string | null;
  operatingHours: string | null;
  metadata: Record<string, any> | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

interface WarehouseCreateRequest {
  name: string;
  code: string;
  slug?: string;
  description?: string;
  warehouseType?: WarehouseType;
  address?: string;
  city?: string;
  state?: string;
  country?: string;
  postalCode?: string;
  phone?: string;
  email?: string;
  managerName?: string;
  maxCapacity?: number;
  capacityUnit?: string;
  isActive?: boolean;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `slug` | `slug` | string | No | Yes | No |
| `description` | `description` | string | No | No | No |
| `code` | `code` | string | Yes | Yes | Yes |
| `warehouseType` | `warehouse_type` | string | No | Yes | Yes |
| `address` | `address` | string | No | No | No |
| `city` | `city` | string | No | Yes | Yes |
| `state` | `state` | string | No | No | No |
| `country` | `country` | string | No | No | No |
| `postalCode` | `postal_code` | string | No | No | No |
| `phone` | `phone` | string | No | No | No |
| `email` | `email` | string | No | No | No |
| `managerName` | `manager_name` | string | No | No | No |
| `maxCapacity` | `max_capacity` | number | No | No | No |
| `capacityUnit` | `capacity_unit` | string | No | No | No |
| `operatingHours` | `operating_hours` | string | No | No | No |
| `metadata` | `metadata` | object | No | No | No |
| `isActive` | `is_active` | boolean | No | Yes | Yes |

#### Relationships

- `locations` → WarehouseLocation[] (hasMany)
- `stock` → Stock[] (hasMany)
- `productBatches` → ProductBatch[] (hasMany)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[name]` | `Main` | Exact match on name |
| `filter[search_name]` | `Main` | LIKE search on name |
| `filter[code]` | `WH001` | Exact match on code |
| `filter[search_code]` | `WH` | LIKE search on code |
| `filter[warehouse_type]` | `main` | Filter by type |
| `filter[is_active]` | `true` | Filter by active status |

---

### 2. WarehouseLocation

**Endpoint:** `/warehouse-locations`
**Resource Type:** `warehouse-locations`

#### TypeScript Interface

```typescript
type LocationType = 'shelf' | 'bin' | 'pallet' | 'floor' | 'zone' | 'dock';

interface WarehouseLocation {
  id: string;
  warehouseId: number;
  name: string;
  code: string;
  description: string | null;
  locationType: LocationType;
  aisle: string | null;
  rack: string | null;
  shelf: string | null;
  level: string | null;
  position: string | null;
  barcode: string | null;
  maxWeight: number | null;
  maxVolume: number | null;
  dimensions: string | null;
  isActive: boolean;
  isPickable: boolean;
  isReceivable: boolean;
  priority: number;
  metadata: any[] | null;
  createdAt: string;
  updatedAt: string;
}

interface WarehouseLocationCreateRequest {
  warehouseId: number;
  name: string;
  code: string;
  description?: string;
  locationType?: LocationType;
  aisle?: string;
  rack?: string;
  shelf?: string;
  level?: string;
  position?: string;
  barcode?: string;
  maxWeight?: number;
  maxVolume?: number;
  dimensions?: string;
  isActive?: boolean;
  isPickable?: boolean;
  isReceivable?: boolean;
  priority?: number;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `warehouseId` | `warehouse_id` | number | Yes | No | Yes |
| `name` | `name` | string | Yes | Yes | Yes |
| `code` | `code` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `locationType` | `location_type` | string | No | Yes | Yes |
| `aisle` | `aisle` | string | No | No | No |
| `rack` | `rack` | string | No | No | No |
| `shelf` | `shelf` | string | No | No | No |
| `level` | `level` | string | No | No | No |
| `position` | `position` | string | No | No | No |
| `barcode` | `barcode` | string | No | No | No |
| `maxWeight` | `max_weight` | number | No | No | No |
| `maxVolume` | `max_volume` | number | No | No | No |
| `dimensions` | `dimensions` | string | No | No | No |
| `isActive` | `is_active` | boolean | No | Yes | Yes |
| `isPickable` | `is_pickable` | boolean | No | No | Yes |
| `isReceivable` | `is_receivable` | boolean | No | No | Yes |
| `priority` | `priority` | number | No | Yes | No |

#### Relationships

- `warehouse` → Warehouse (belongsTo)
- `stock` → Stock[] (hasMany)
- `productBatches` → ProductBatch[] (hasMany)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[name]` | `Shelf A1` | Exact match |
| `filter[search_name]` | `Shelf` | LIKE search |
| `filter[code]` | `LOC001` | Exact match |
| `filter[search_code]` | `LOC` | LIKE search |
| `filter[location_type]` | `shelf` | Filter by type |
| `filter[warehouse_id]` | `1` | Filter by warehouse |
| `filter[is_active]` | `true` | Filter by active |
| `filter[is_pickable]` | `true` | Filter by pickable |
| `filter[is_receivable]` | `true` | Filter by receivable |

---

### 3. Stock

**Endpoint:** `/stocks`
**Resource Type:** `stocks`

#### TypeScript Interface

```typescript
type StockStatus = 'available' | 'reserved' | 'quarantine' | 'damaged';

interface Stock {
  id: string;
  productId: number;
  warehouseId: number;
  locationId: number | null;
  quantity: number;
  reservedQuantity: number;
  availableQuantity: number;     // Auto-calculated (readOnly)
  minimumStock: number;
  maximumStock: number | null;
  reorderPoint: number;
  unitCost: number;
  totalValue: number;            // Auto-calculated (readOnly)
  status: StockStatus;
  lastMovementDate: string | null;
  lastMovementType: string | null;
  batchInfo: Record<string, any> | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}

interface StockCreateRequest {
  productId: number;
  warehouseId: number;
  locationId?: number;
  quantity: number;
  reservedQuantity?: number;
  minimumStock?: number;
  maximumStock?: number;
  reorderPoint?: number;
  unitCost?: number;
  status?: StockStatus;
  batchInfo?: Record<string, any>;
  metadata?: Record<string, any>;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `productId` | `product_id` | number | Yes | No | Yes |
| `warehouseId` | `warehouse_id` | number | Yes | No | Yes |
| `locationId` | `warehouse_location_id` | number | No | No | Yes |
| `quantity` | `quantity` | number | Yes | Yes | No |
| `reservedQuantity` | `reserved_quantity` | number | No | Yes | No |
| `availableQuantity` | `available_quantity` | number | No | Yes | No |
| `minimumStock` | `minimum_stock` | number | No | Yes | No |
| `maximumStock` | `maximum_stock` | number | No | Yes | No |
| `reorderPoint` | `reorder_point` | number | No | Yes | No |
| `unitCost` | `unit_cost` | number | No | Yes | No |
| `totalValue` | `total_value` | number | No | Yes | No |
| `status` | `status` | string | No | Yes | Yes |
| `lastMovementDate` | `last_movement_date` | datetime | No | Yes | No |
| `lastMovementType` | `last_movement_type` | string | No | No | No |

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)
- `location` → WarehouseLocation (belongsTo)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `available` | Filter by status |
| `filter[product_id]` | `123` | Filter by product |
| `filter[warehouse_id]` | `1` | Filter by warehouse |
| `filter[warehouse_location_id]` | `5` | Filter by location |
| `filter[search]` | `SKU123` | Search scope |

---

### 4. ProductBatch

**Endpoint:** `/product-batches`
**Resource Type:** `product-batches`

#### TypeScript Interface

```typescript
type BatchStatus = 'available' | 'reserved' | 'quarantine' | 'expired' | 'depleted';

interface ProductBatch {
  id: string;
  batchNumber: string;
  lotNumber: string | null;
  manufacturingDate: string | null;
  expirationDate: string | null;
  bestBeforeDate: string | null;
  initialQuantity: number;
  currentQuantity: number;
  reservedQuantity: number;
  availableQuantity: number;      // Auto-calculated (readOnly)
  unitCost: number;
  totalValue: number;             // Auto-calculated (readOnly)
  status: BatchStatus;
  supplierName: string | null;
  supplierBatch: string | null;
  qualityNotes: string | null;
  testResults: Record<string, any> | null;
  certifications: any[] | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}

interface ProductBatchCreateRequest {
  batchNumber: string;
  lotNumber?: string;
  manufacturingDate?: string;
  expirationDate?: string;
  bestBeforeDate?: string;
  initialQuantity: number;
  currentQuantity?: number;
  reservedQuantity?: number;
  unitCost?: number;
  status?: BatchStatus;
  supplierName?: string;
  supplierBatch?: string;
  qualityNotes?: string;
  testResults?: Record<string, any>;
  certifications?: any[];
  metadata?: Record<string, any>;
  productId: number;    // relationship
  warehouseId: number;  // relationship
  warehouseLocationId?: number;  // relationship
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `batchNumber` | `batch_number` | string | Yes | Yes | Yes |
| `lotNumber` | `lot_number` | string | No | Yes | Yes |
| `manufacturingDate` | `manufacturing_date` | date | No | Yes | No |
| `expirationDate` | `expiration_date` | date | No | Yes | No |
| `bestBeforeDate` | `best_before_date` | date | No | Yes | No |
| `initialQuantity` | `initial_quantity` | number | Yes | Yes | No |
| `currentQuantity` | `current_quantity` | number | No | Yes | No |
| `reservedQuantity` | `reserved_quantity` | number | No | Yes | No |
| `availableQuantity` | `available_quantity` | number | No | Yes | No |
| `unitCost` | `unit_cost` | number | No | Yes | No |
| `totalValue` | `total_value` | number | No | Yes | No |
| `status` | `status` | string | No | Yes | Yes |
| `supplierName` | `supplier_name` | string | No | No | No |
| `supplierBatch` | `supplier_batch` | string | No | No | No |
| `qualityNotes` | `quality_notes` | string | No | No | No |

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)
- `warehouseLocation` → WarehouseLocation (belongsTo)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `available` | Filter by status |
| `filter[batch_number]` | `BATCH-001` | Filter by batch number |
| `filter[lot_number]` | `LOT-001` | Filter by lot number |
| `filter[product_id]` | `123` | Filter by product |
| `filter[warehouse_id]` | `1` | Filter by warehouse |
| `filter[warehouse_location_id]` | `5` | Filter by location |

---

### 5. InventoryMovement

**Endpoint:** `/inventory-movements`
**Resource Type:** `inventory-movements`

#### TypeScript Interface

```typescript
type MovementType = 'entry' | 'exit' | 'adjustment' | 'transfer';
type ReferenceType = 'purchase_order' | 'sales_order' | 'transfer' | 'adjustment' | 'return';
type MovementStatus = 'pending' | 'completed' | 'cancelled';
type GLPostingStatus = 'pending' | 'posted' | 'failed';

interface InventoryMovement {
  id: string;
  productId: number;
  warehouseId: number;
  locationId: number | null;
  destinationWarehouseId: number | null;
  destinationLocationId: number | null;
  userId: number;
  movementType: MovementType;
  referenceType: ReferenceType | null;
  referenceId: number | null;
  movementDate: string;
  description: string | null;
  quantity: number;
  unitCost: number;
  totalValue: number;
  status: MovementStatus;
  previousStock: number;
  newStock: number;
  // Quality check fields
  qualityChecked: boolean;
  qualityCheckedAt: string | null;
  qualityCheckedBy: number | null;
  qualityCheckNotes: string | null;
  // Batch and metadata
  batchInfo: Record<string, any> | null;
  metadata: Record<string, any> | null;
  // GL Integration
  glJournalEntryId: number | null;
  glPostingStatus: GLPostingStatus;
  costPerUnit: number | null;
  totalCost: number | null;
  glPostingNotes: string | null;
  createdAt: string;
  updatedAt: string;
}

interface InventoryMovementCreateRequest {
  productId: number;
  warehouseId: number;
  locationId?: number;
  destinationWarehouseId?: number;     // Required for transfers
  destinationLocationId?: number;
  userId: number;
  movementType: MovementType;
  referenceType?: ReferenceType;
  referenceId?: number;
  movementDate?: string;
  description?: string;
  quantity: number;
  unitCost?: number;
  status?: MovementStatus;
  batchInfo?: Record<string, any>;
  metadata?: Record<string, any>;
}
```

#### Movement Types

- **entry**: Stock entering warehouse (purchases, returns, production output)
- **exit**: Stock leaving warehouse (sales, consumption, production input)
- **adjustment**: Stock count adjustments (corrections, cycle counts, damages)
- **transfer**: Stock transfer between warehouses

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `productId` | `product_id` | number | Yes | No | Yes |
| `warehouseId` | `warehouse_id` | number | Yes | No | Yes |
| `locationId` | `warehouse_location_id` | number | No | No | No |
| `destinationWarehouseId` | `destination_warehouse_id` | number | No | No | Yes |
| `destinationLocationId` | `destination_location_id` | number | No | No | No |
| `userId` | `user_id` | number | Yes | No | Yes |
| `movementType` | `movement_type` | string | Yes | Yes | Yes |
| `referenceType` | `reference_type` | string | No | Yes | Yes |
| `referenceId` | `reference_id` | number | No | No | Yes |
| `movementDate` | `movement_date` | datetime | No | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `quantity` | `quantity` | number | Yes | Yes | No |
| `unitCost` | `unit_cost` | number | No | Yes | No |
| `totalValue` | `total_value` | number | No | Yes | No |
| `status` | `status` | string | No | Yes | Yes |
| `previousStock` | `previous_stock` | number | No | No | No |
| `newStock` | `new_stock` | number | No | No | No |
| `qualityChecked` | `quality_checked` | boolean | No | Yes | No |
| `qualityCheckedAt` | `quality_checked_at` | datetime | No | Yes | No |
| `glJournalEntryId` | `gl_journal_entry_id` | number | No | Yes | No |
| `glPostingStatus` | `gl_posting_status` | string | No | Yes | No |

#### Relationships

- `product` → Product (belongsTo)
- `warehouse` → Warehouse (belongsTo)
- `location` → WarehouseLocation (belongsTo)
- `destinationWarehouse` → Warehouse (belongsTo)
- `destinationLocation` → WarehouseLocation (belongsTo)
- `user` → User (belongsTo)
- `qualityChecker` → User (belongsTo)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[movementType]` | `entry` | Filter by movement type |
| `filter[referenceType]` | `purchase_order` | Filter by reference type |
| `filter[referenceId]` | `123` | Filter by reference ID |
| `filter[product]` | `5` | Filter by product ID (WhereIn) |
| `filter[warehouse]` | `1` | Filter by warehouse ID (WhereIn) |
| `filter[destinationWarehouse]` | `2` | Filter by destination warehouse |
| `filter[status]` | `completed` | Filter by status |
| `filter[user]` | `1` | Filter by user ID |
| `filter[movementDate]` | `2024-01-15` | Filter by exact date |
| `filter[dateFrom]` | `2024-01-01` | Filter movements from date |
| `filter[dateTo]` | `2024-01-31` | Filter movements to date |

---

## Common Use Cases

### 1. Check Stock Availability

```typescript
async function checkStockAvailability(productId: number, warehouseId: number) {
  const response = await fetch(
    `/api/v1/stocks?filter[product_id]=${productId}&filter[warehouse_id]=${warehouseId}&include=product,warehouse`,
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
    reservedQuantity: stock.reservedQuantity,
    needsReorder: stock.quantity <= stock.reorderPoint
  };
}
```

### 2. Record Stock Entry

```typescript
async function recordStockEntry(
  productId: number,
  warehouseId: number,
  quantity: number,
  referenceType: string,
  referenceId: number,
  batchInfo?: Record<string, any>
) {
  const payload = {
    data: {
      type: "inventory-movements",
      attributes: {
        productId,
        warehouseId,
        userId: currentUser.id,
        movementType: "entry",
        referenceType,
        referenceId,
        quantity,
        movementDate: new Date().toISOString(),
        description: `Stock entry from ${referenceType} #${referenceId}`,
        batchInfo
      }
    }
  };

  const response = await fetch('/api/v1/inventory-movements', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(payload)
  });

  return response.json();
}
```

### 3. Transfer Stock Between Warehouses

```typescript
async function transferStock(
  productId: number,
  fromWarehouseId: number,
  toWarehouseId: number,
  quantity: number,
  description?: string
) {
  const payload = {
    data: {
      type: "inventory-movements",
      attributes: {
        productId,
        warehouseId: fromWarehouseId,
        destinationWarehouseId: toWarehouseId,
        userId: currentUser.id,
        movementType: "transfer",
        quantity,
        movementDate: new Date().toISOString(),
        description: description || "Stock transfer"
      }
    }
  };

  const response = await fetch('/api/v1/inventory-movements', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return response.json();
}
```

### 4. Get Movement History

```typescript
async function getMovementHistory(
  productId: number,
  warehouseId?: number,
  dateFrom?: string,
  dateTo?: string
) {
  let url = `/api/v1/inventory-movements?filter[product]=${productId}&include=user,warehouse&sort=-movementDate`;

  if (warehouseId) {
    url += `&filter[warehouse]=${warehouseId}`;
  }
  if (dateFrom) {
    url += `&filter[dateFrom]=${dateFrom}`;
  }
  if (dateTo) {
    url += `&filter[dateTo]=${dateTo}`;
  }

  const response = await fetch(url, { headers });
  return response.json();
}
```

### 5. Get Low Stock Products

```typescript
async function getLowStockProducts(warehouseId: number) {
  const response = await fetch(
    `/api/v1/stocks?filter[warehouse_id]=${warehouseId}&include=product`,
    { headers }
  );

  const data = await response.json();

  return data.data.filter((stock: any) => {
    const attrs = stock.attributes;
    return attrs.quantity <= attrs.reorderPoint;
  });
}
```

### 6. Create Product Batch with FEFO Tracking

```typescript
async function createBatch(
  productId: number,
  warehouseId: number,
  batchNumber: string,
  quantity: number,
  expirationDate: string,
  supplierInfo?: { name: string; batch: string }
) {
  const payload = {
    data: {
      type: "product-batches",
      attributes: {
        batchNumber,
        initialQuantity: quantity,
        currentQuantity: quantity,
        expirationDate,
        manufacturingDate: new Date().toISOString().split('T')[0],
        status: "available",
        supplierName: supplierInfo?.name,
        supplierBatch: supplierInfo?.batch
      },
      relationships: {
        product: {
          data: { type: "products", id: String(productId) }
        },
        warehouse: {
          data: { type: "warehouses", id: String(warehouseId) }
        }
      }
    }
  };

  const response = await fetch('/api/v1/product-batches', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return response.json();
}
```

---

## Permissions

### Role-Based Access

| Role | Read | Create | Update | Delete |
|------|------|--------|--------|--------|
| **God** | Yes | Yes | Yes | Yes |
| **Admin** | Yes | Yes | Yes | Yes |
| **Tech** | Yes | No | No | No |
| **Customer** | No | No | No | No |

### Permission Names

| Entity | index | show | store | update | destroy |
|--------|-------|------|-------|--------|---------|
| warehouses | `warehouses.index` | `warehouses.show` | `warehouses.store` | `warehouses.update` | `warehouses.destroy` |
| warehouse-locations | `warehouse-locations.index` | `warehouse-locations.show` | `warehouse-locations.store` | `warehouse-locations.update` | `warehouse-locations.destroy` |
| stocks | `stocks.index` | `stocks.show` | `stocks.store` | `stocks.update` | `stocks.destroy` |
| product-batches | `product-batches.index` | `product-batches.show` | `product-batches.store` | `product-batches.update` | `product-batches.destroy` |
| inventory-movements | `inventory-movements.index` | `inventory-movements.show` | `inventory-movements.store` | `inventory-movements.update` | `inventory-movements.destroy` |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/warehouses` - List warehouses
- `GET /api/v1/warehouse-locations` - List locations
- `GET /api/v1/stocks` - Check stock levels
- `GET /api/v1/product-batches` - List batches (FEFO tracking)
- `GET /api/v1/inventory-movements` - Movement history

**Key Features:**
- Auto-calculated fields: `availableQuantity`, `totalValue`
- Quality check tracking on movements
- GL integration for accounting
- FEFO (First Expired, First Out) batch support
- Multi-warehouse transfers with location tracking

**Related Modules:**
- [Product Module](PRODUCT_FRONTEND_GUIDE.md) - Product catalog
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Process sales orders
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Receive purchase orders
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - GL posting for movements
