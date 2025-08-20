# 📋 API Documentation - Ecommerce

Auto-generated API documentation.

**Generated:** 2025-08-20 11:02:11

## 📄 CartItem

**Resource Type:** `cart-items`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cart-items` | List all CartItems |
| POST | `/api/v1/cart-items` | Create new CartItem |
| GET | `/api/v1/cart-items/{id}` | Show specific CartItem |
| PATCH | `/api/v1/cart-items/{id}` | Update CartItem |
| DELETE | `/api/v1/cart-items/{id}` | Delete CartItem |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `shoppingCartId` | string | Auto-detected field |
| `productId` | string | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitPrice` | number | Auto-detected field |
| `originalPrice` | number | Auto-detected field |
| `discountPercent` | number | Auto-detected field |
| `discountAmount` | number | Auto-detected field |
| `subtotal` | number | Auto-detected field |
| `taxRate` | number | Auto-detected field |
| `taxAmount` | number | Auto-detected field |
| `total` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `status` | string | Auto-detected field |
| `shoppingCart` | relationship | Auto-detected field |
| `product` | relationship | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/cart-items?filter[field]=value
```

#### Sorting
```
GET /api/v1/cart-items?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/cart-items?page[number]=1&page[size]=20
```

## 📄 Coupon

**Resource Type:** `coupons`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/coupons` | List all Coupons |
| POST | `/api/v1/coupons` | Create new Coupon |
| GET | `/api/v1/coupons/{id}` | Show specific Coupon |
| PATCH | `/api/v1/coupons/{id}` | Update Coupon |
| DELETE | `/api/v1/coupons/{id}` | Delete Coupon |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `code` | string | Auto-detected field |
| `name` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `couponType` | string | Auto-detected field |
| `value` | number | Auto-detected field |
| `minAmount` | number | Auto-detected field |
| `maxAmount` | number | Auto-detected field |
| `maxUses` | number | Auto-detected field |
| `usedCount` | number | Auto-detected field |
| `startsAt` | datetime | Auto-detected field |
| `expiresAt` | datetime | Auto-detected field |
| `isActive` | boolean | Auto-detected field |
| `customerIds` | unknown | Auto-detected field |
| `productIds` | unknown | Auto-detected field |
| `categoryIds` | unknown | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/coupons?filter[field]=value
```

#### Sorting
```
GET /api/v1/coupons?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/coupons?page[number]=1&page[size]=20
```

## 📄 ShoppingCart

**Resource Type:** `shopping-carts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/shopping-carts` | List all ShoppingCarts |
| POST | `/api/v1/shopping-carts` | Create new ShoppingCart |
| GET | `/api/v1/shopping-carts/{id}` | Show specific ShoppingCart |
| PATCH | `/api/v1/shopping-carts/{id}` | Update ShoppingCart |
| DELETE | `/api/v1/shopping-carts/{id}` | Delete ShoppingCart |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `sessionId` | string | Auto-detected field |
| `userId` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `expiresAt` | datetime | Auto-detected field |
| `totalAmount` | number | Auto-detected field |
| `currency` | string | Auto-detected field |
| `couponCode` | string | Auto-detected field |
| `discountAmount` | number | Auto-detected field |
| `taxAmount` | number | Auto-detected field |
| `shippingAmount` | number | Auto-detected field |
| `notes` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `cartItems` | relationship[] | Auto-detected field |
| `user` | relationship | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/shopping-carts?filter[field]=value
```

#### Sorting
```
GET /api/v1/shopping-carts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/shopping-carts?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

