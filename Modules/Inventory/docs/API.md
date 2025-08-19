# 📋 API Documentation - Inventory

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 InventoryMovement

**Resource Type:** `inventory-movements`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/inventory-movements` | List all InventoryMovements |
| POST | `/api/v1/inventory-movements` | Create new InventoryMovement |
| GET | `/api/v1/inventory-movements/{id}` | Show specific InventoryMovement |
| PATCH | `/api/v1/inventory-movements/{id}` | Update InventoryMovement |
| DELETE | `/api/v1/inventory-movements/{id}` | Delete InventoryMovement |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `productId` | number | Auto-detected field |
| `warehouseId` | number | Auto-detected field |
| `locationId` | number | Auto-detected field |
| `destinationWarehouseId` | number | Auto-detected field |
| `destinationLocationId` | number | Auto-detected field |
| `userId` | number | Auto-detected field |
| `movementType` | string | Auto-detected field |
| `referenceType` | string | Auto-detected field |
| `referenceId` | number | Auto-detected field |
| `movementDate` | datetime | Auto-detected field |
| `description` | string | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitCost` | number | Auto-detected field |
| `totalValue` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `previousStock` | number | Auto-detected field |
| `newStock` | number | Auto-detected field |
| `batchInfo` | object | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `product` | relationship | Auto-detected field |
| `warehouse` | relationship | Auto-detected field |
| `location` | relationship | Auto-detected field |
| `destinationWarehouse` | relationship | Auto-detected field |
| `destinationLocation` | relationship | Auto-detected field |
| `user` | relationship | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/inventory-movements?filter[field]=value
```

#### Sorting
```
GET /api/v1/inventory-movements?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/inventory-movements?page[number]=1&page[size]=20
```

## 📄 ProductBatch

**Resource Type:** `product-batches`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/product-batches` | List all ProductBatches |
| POST | `/api/v1/product-batches` | Create new ProductBatch |
| GET | `/api/v1/product-batches/{id}` | Show specific ProductBatch |
| PATCH | `/api/v1/product-batches/{id}` | Update ProductBatch |
| DELETE | `/api/v1/product-batches/{id}` | Delete ProductBatch |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `batchNumber` | string | Auto-detected field |
| `lotNumber` | string | Auto-detected field |
| `manufacturingDate` | datetime | Auto-detected field |
| `expirationDate` | datetime | Auto-detected field |
| `bestBeforeDate` | datetime | Auto-detected field |
| `initialQuantity` | number | Auto-detected field |
| `currentQuantity` | number | Auto-detected field |
| `reservedQuantity` | number | Auto-detected field |
| `availableQuantity` | number | Auto-detected field |
| `unitCost` | number | Auto-detected field |
| `totalValue` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `supplierName` | string | Auto-detected field |
| `supplierBatch` | string | Auto-detected field |
| `qualityNotes` | string | Auto-detected field |
| `testResults` | object | Auto-detected field |
| `certifications` | object | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `product` | relationship | Auto-detected field |
| `warehouse` | relationship | Auto-detected field |
| `warehouseLocation` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/product-batches?filter[field]=value
```

#### Sorting
```
GET /api/v1/product-batches?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/product-batches?page[number]=1&page[size]=20
```

## 📄 Stock

**Resource Type:** `stocks`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/stocks` | List all Stocks |
| POST | `/api/v1/stocks` | Create new Stock |
| GET | `/api/v1/stocks/{id}` | Show specific Stock |
| PATCH | `/api/v1/stocks/{id}` | Update Stock |
| DELETE | `/api/v1/stocks/{id}` | Delete Stock |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `productId` | number | Auto-detected field |
| `warehouseId` | number | Auto-detected field |
| `locationId` | number | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `reservedQuantity` | number | Auto-detected field |
| `availableQuantity` | number | Auto-detected field |
| `minimumStock` | number | Auto-detected field |
| `maximumStock` | number | Auto-detected field |
| `reorderPoint` | number | Auto-detected field |
| `unitCost` | number | Auto-detected field |
| `totalValue` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `lastMovementDate` | datetime | Auto-detected field |
| `lastMovementType` | string | Auto-detected field |
| `batchInfo` | object | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `product` | relationship | Auto-detected field |
| `warehouse` | relationship | Auto-detected field |
| `location` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/stocks?filter[field]=value
```

#### Sorting
```
GET /api/v1/stocks?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/stocks?page[number]=1&page[size]=20
```

## 📄 WarehouseLocation

**Resource Type:** `warehouse-locations`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/warehouse-locations` | List all WarehouseLocations |
| POST | `/api/v1/warehouse-locations` | Create new WarehouseLocation |
| GET | `/api/v1/warehouse-locations/{id}` | Show specific WarehouseLocation |
| PATCH | `/api/v1/warehouse-locations/{id}` | Update WarehouseLocation |
| DELETE | `/api/v1/warehouse-locations/{id}` | Delete WarehouseLocation |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `warehouseId` | number | Auto-detected field |
| `name` | string | Auto-detected field |
| `code` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `locationType` | string | Auto-detected field |
| `aisle` | string | Auto-detected field |
| `rack` | string | Auto-detected field |
| `shelf` | string | Auto-detected field |
| `level` | string | Auto-detected field |
| `position` | string | Auto-detected field |
| `barcode` | string | Auto-detected field |
| `maxWeight` | number | Auto-detected field |
| `maxVolume` | number | Auto-detected field |
| `dimensions` | string | Auto-detected field |
| `isActive` | boolean | Auto-detected field |
| `isPickable` | boolean | Auto-detected field |
| `isReceivable` | boolean | Auto-detected field |
| `priority` | number | Auto-detected field |
| `metadata` | unknown | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `warehouse` | relationship | Auto-detected field |
| `stock` | relationship[] | Auto-detected field |
| `productBatches` | relationship[] | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/warehouse-locations?filter[field]=value
```

#### Sorting
```
GET /api/v1/warehouse-locations?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/warehouse-locations?page[number]=1&page[size]=20
```

## 📄 Warehouse

**Resource Type:** `warehouses`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/warehouses` | List all Warehouses |
| POST | `/api/v1/warehouses` | Create new Warehouse |
| GET | `/api/v1/warehouses/{id}` | Show specific Warehouse |
| PATCH | `/api/v1/warehouses/{id}` | Update Warehouse |
| DELETE | `/api/v1/warehouses/{id}` | Delete Warehouse |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `name` | string | Auto-detected field |
| `slug` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `code` | string | Auto-detected field |
| `warehouseType` | string | Auto-detected field |
| `address` | string | Auto-detected field |
| `city` | string | Auto-detected field |
| `state` | string | Auto-detected field |
| `country` | string | Auto-detected field |
| `postalCode` | string | Auto-detected field |
| `phone` | string | Auto-detected field |
| `email` | string | Auto-detected field |
| `managerName` | string | Auto-detected field |
| `maxCapacity` | number | Auto-detected field |
| `capacityUnit` | string | Auto-detected field |
| `operatingHours` | string | Auto-detected field |
| `metadata` | string | Auto-detected field |
| `isActive` | boolean | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `locations` | relationship[] | Auto-detected field |
| `stock` | relationship[] | Auto-detected field |
| `productBatches` | relationship[] | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/warehouses?filter[field]=value
```

#### Sorting
```
GET /api/v1/warehouses?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/warehouses?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

