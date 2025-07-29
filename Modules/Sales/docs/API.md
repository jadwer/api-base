# 📋 API Documentation - Sales

Auto-generated API documentation.

**Generated:** 2025-07-29 14:52:13

## 📄 Customer

**Resource Type:** `customers`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/customers` | List all Customers |
| POST | `/api/v1/customers` | Create new Customer |
| GET | `/api/v1/customers/{id}` | Show specific Customer |
| PATCH | `/api/v1/customers/{id}` | Update Customer |
| DELETE | `/api/v1/customers/{id}` | Delete Customer |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Auto-detected field |
| `email` | string | Auto-detected field |
| `phone` | string | Auto-detected field |
| `address` | string | Auto-detected field |
| `city` | string | Auto-detected field |
| `state` | string | Auto-detected field |
| `country` | string | Auto-detected field |
| `classification` | string | Auto-detected field |
| `credit_limit` | number | Auto-detected field |
| `current_credit` | number | Auto-detected field |
| `is_active` | boolean | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `created_at` | datetime | Auto-detected field |
| `updated_at` | datetime | Auto-detected field |
| `salesOrders` | relationship[] | Auto-detected field |
| `name` | unknown | Auto-detected field |
| `email` | unknown | Auto-detected field |
| `classification` | unknown | Auto-detected field |
| `is_active` | unknown | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/customers?filter[field]=value
```

#### Sorting
```
GET /api/v1/customers?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/customers?page[number]=1&page[size]=20
```

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
| `salesOrderId` | unknown | Auto-detected field |
| `productId` | unknown | Auto-detected field |
| `quantity` | unknown | Auto-detected field |
| `unitPrice` | unknown | Auto-detected field |
| `total` | unknown | Auto-detected field |

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
| `customer_id` | number | Auto-detected field |
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
| `order_number` | unknown | Auto-detected field |
| `status` | unknown | Auto-detected field |
| `customer` | unknown | Auto-detected field |
| `order_date` | unknown | Auto-detected field |

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

