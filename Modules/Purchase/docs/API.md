# 📋 API Documentation - Purchase

Auto-generated API documentation.

**Generated:** 2025-08-20 01:05:10

## 📄 PurchaseOrderItem

**Resource Type:** `purchase-order-items`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-order-items` | List all PurchaseOrderItems |
| POST | `/api/v1/purchase-order-items` | Create new PurchaseOrderItem |
| GET | `/api/v1/purchase-order-items/{id}` | Show specific PurchaseOrderItem |
| PATCH | `/api/v1/purchase-order-items/{id}` | Update PurchaseOrderItem |
| DELETE | `/api/v1/purchase-order-items/{id}` | Delete PurchaseOrderItem |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitPrice` | number | Auto-detected field |
| `subtotal` | number | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `purchaseOrder` | relationship | Auto-detected field |
| `product` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/purchase-order-items?filter[field]=value
```

#### Sorting
```
GET /api/v1/purchase-order-items?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/purchase-order-items?page[number]=1&page[size]=20
```

## 📄 PurchaseOrder

**Resource Type:** `purchase-orders`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-orders` | List all PurchaseOrders |
| POST | `/api/v1/purchase-orders` | Create new PurchaseOrder |
| GET | `/api/v1/purchase-orders/{id}` | Show specific PurchaseOrder |
| PATCH | `/api/v1/purchase-orders/{id}` | Update PurchaseOrder |
| DELETE | `/api/v1/purchase-orders/{id}` | Delete PurchaseOrder |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contact_id` | number | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `orderDate` | datetime | Auto-detected field |
| `status` | string | Auto-detected field |
| `totalAmount` | number | Auto-detected field |
| `notes` | string | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `contact` | relationship | Auto-detected field |
| `purchaseOrderItems` | relationship[] | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/purchase-orders?filter[field]=value
```

#### Sorting
```
GET /api/v1/purchase-orders?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/purchase-orders?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

