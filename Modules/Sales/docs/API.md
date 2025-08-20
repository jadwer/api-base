# 📋 API Documentation - Sales

Auto-generated API documentation.

**Generated:** 2025-08-20 00:30:33

## 📄 SalesOrderItem

**Resource Type:** `sales-order-items`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sales-order-items` | List all SalesOrderItems |
| POST | `/api/v1/sales-order-items` | Create new SalesOrderItem |
| GET | `/api/v1/sales-order-items/{id}` | Show specific SalesOrderItem |
| PATCH | `/api/v1/sales-order-items/{id}` | Update SalesOrderItem |
| DELETE | `/api/v1/sales-order-items/{id}` | Delete SalesOrderItem |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `salesOrderId` | number | Auto-detected field |
| `productId` | number | Auto-detected field |
| `salesOrder` | relationship | Auto-detected field |
| `product` | relationship | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitPrice` | number | Auto-detected field |
| `discount` | number | Auto-detected field |
| `total` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/sales-order-items?filter[field]=value
```

#### Sorting
```
GET /api/v1/sales-order-items?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/sales-order-items?page[number]=1&page[size]=20
```

## 📄 SalesOrder

**Resource Type:** `sales-orders`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sales-orders` | List all SalesOrders |
| POST | `/api/v1/sales-orders` | Create new SalesOrder |
| GET | `/api/v1/sales-orders/{id}` | Show specific SalesOrder |
| PATCH | `/api/v1/sales-orders/{id}` | Update SalesOrder |
| DELETE | `/api/v1/sales-orders/{id}` | Delete SalesOrder |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contact_id` | number | Auto-detected field |
| `contact` | relationship | Auto-detected field |
| `customer` | relationship | Auto-detected field |
| `order_number` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `order_date` | datetime | Auto-detected field |
| `approved_at` | datetime | Auto-detected field |
| `delivered_at` | datetime | Auto-detected field |
| `subtotal_amount` | number | Auto-detected field |
| `tax_amount` | number | Auto-detected field |
| `discount_total` | number | Auto-detected field |
| `total_amount` | number | Auto-detected field |
| `notes` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `items` | relationship[] | Auto-detected field |
| `created_at` | datetime | Auto-detected field |
| `updated_at` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/sales-orders?filter[field]=value
```

#### Sorting
```
GET /api/v1/sales-orders?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/sales-orders?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

