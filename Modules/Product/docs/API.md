# 📋 API Documentation - Product

Auto-generated API documentation.

**Generated:** 2025-08-08 23:11:40

## 📄 Brand

**Resource Type:** `brands`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/brands` | List all Brands |
| POST | `/api/v1/brands` | Create new Brand |
| GET | `/api/v1/brands/{id}` | Show specific Brand |
| PATCH | `/api/v1/brands/{id}` | Update Brand |
| DELETE | `/api/v1/brands/{id}` | Delete Brand |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `slug` | string | Auto-detected field |
| `products` | relationship[] | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `name` | unknown | Auto-detected field |
| `slug` | unknown | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/brands?filter[field]=value
```

#### Sorting
```
GET /api/v1/brands?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/brands?page[number]=1&page[size]=20
```

## 📄 Product

**Resource Type:** `products`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | List all Products |
| POST | `/api/v1/products` | Create new Product |
| GET | `/api/v1/products/{id}` | Show specific Product |
| PATCH | `/api/v1/products/{id}` | Update Product |
| DELETE | `/api/v1/products/{id}` | Delete Product |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Auto-detected field |
| `sku` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `fullDescription` | string | Auto-detected field |
| `price` | number | Auto-detected field |
| `cost` | number | Auto-detected field |
| `iva` | boolean | Auto-detected field |
| `imgPath` | string | Auto-detected field |
| `datasheetPath` | string | Auto-detected field |
| `unit` | relationship | Auto-detected field |
| `category` | relationship | Auto-detected field |
| `brand` | relationship | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `name` | unknown | Auto-detected field |
| `sku` | unknown | Auto-detected field |
| `search_name` | unknown | Auto-detected field |
| `search_sku` | unknown | Auto-detected field |
| `unit_id` | unknown | Auto-detected field |
| `category_id` | unknown | Auto-detected field |
| `brand_id` | unknown | Auto-detected field |
| `brands` | unknown | Auto-detected field |
| `categories` | unknown | Auto-detected field |
| `units` | unknown | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/products?filter[field]=value
```

#### Sorting
```
GET /api/v1/products?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/products?page[number]=1&page[size]=20
```

## 📄 Unit

**Resource Type:** `units`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/units` | List all Units |
| POST | `/api/v1/units` | Create new Unit |
| GET | `/api/v1/units/{id}` | Show specific Unit |
| PATCH | `/api/v1/units/{id}` | Update Unit |
| DELETE | `/api/v1/units/{id}` | Delete Unit |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Auto-detected field |
| `code` | string | Auto-detected field |
| `unitType` | string | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `name` | unknown | Auto-detected field |
| `code` | unknown | Auto-detected field |
| `unit_type` | unknown | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/units?filter[field]=value
```

#### Sorting
```
GET /api/v1/units?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/units?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

