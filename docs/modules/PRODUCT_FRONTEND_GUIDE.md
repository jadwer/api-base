# Product Module - Frontend Integration Guide

**Module:** Product
**Entities:** 4 (Product, Category, Brand, Unit)
**Endpoints:** 20
**Base Path:** `/api/v1`

## Overview

The Product module manages your product catalog including products, categories, brands, and units of measure.

**Last Updated:** 2025-11-15 - Added `isActive` field to Product schema

## Entities

### 1. Product

**Endpoint:** `/products`
**Resource Type:** `products`

#### TypeScript Interface

```typescript
interface Product {
  id: string;
  name: string;
  sku: string;
  description: string | null;
  fullDescription: string | null;
  price: number;
  cost: number;
  iva: boolean;
  isActive: boolean;
  imgPath: string | null;
  datasheetPath: string | null;
  unitId: number;
  categoryId: number | null;
  brandId: number | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `sku` | `sku` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `fullDescription` | `full_description` | string | No | No | No |
| `price` | `price` | number | Yes | Yes | Yes |
| `cost` | `cost` | number | Yes | Yes | No |
| `iva` | `iva` | boolean | No | No | No |
| `isActive` | `is_active` | boolean | No | Yes | Yes |
| `imgPath` | `img_path` | string | No | No | No |
| `datasheetPath` | `datasheet_path` | string | No | No | No |
| `unitId` | `unit_id` | number | Yes | No | Yes |
| `categoryId` | `category_id` | number | No | No | Yes |
| `brandId` | `brand_id` | number | No | No | Yes |
| `createdAt` | `created_at` | datetime | Auto | Yes | No |
| `updatedAt` | `updated_at` | datetime | Auto | Yes | No |

#### Relationships

- `unit` → Unit (belongsTo)
- `category` → Category (belongsTo)
- `brand` → Brand (belongsTo)

#### Examples

**List Products with Filters:**
```javascript
// Get all active products from a specific category
const response = await fetch(
  '/api/v1/products?filter[category_id]=5&filter[is_active]=true&sort=-createdAt&include=unit,category,brand',
  { headers }
);
```

**Create Product:**
```javascript
const payload = {
  data: {
    type: "products",
    attributes: {
      name: "Laptop Dell XPS 15",
      sku: "DELL-XPS15-001",
      description: "High-performance laptop",
      fullDescription: "15.6-inch display, Intel Core i7, 16GB RAM, 512GB SSD",
      price: 1499.99,
      cost: 1099.99,
      iva: true,
      isActive: true,
      imgPath: "/images/products/dell-xps15.jpg",
      datasheetPath: "/documents/products/dell-xps15-specs.pdf",
      unitId: 1,
      categoryId: 5,
      brandId: 3
    }
  }
};

const response = await fetch('/api/v1/products', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

---

### 2. Category

**Endpoint:** `/categories`
**Resource Type:** `categories`

#### TypeScript Interface

```typescript
interface Category {
  id: string;
  name: string;
  description: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `createdAt` | `created_at` | datetime | Auto | Yes | No |
| `updatedAt` | `updated_at` | datetime | Auto | Yes | No |

#### Example

```javascript
const payload = {
  data: {
    type: "categories",
    attributes: {
      name: "Electronics",
      description: "Electronic devices and accessories"
    }
  }
};

const response = await fetch('/api/v1/categories', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

---

### 3. Brand

**Endpoint:** `/brands`
**Resource Type:** `brands`

#### TypeScript Interface

```typescript
interface Brand {
  id: string;
  name: string;
  description: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `createdAt` | `created_at` | datetime | Auto | Yes | No |
| `updatedAt` | `updated_at` | datetime | Auto | Yes | No |

---

### 4. Unit

**Endpoint:** `/units`
**Resource Type:** `units`

#### TypeScript Interface

```typescript
interface Unit {
  id: string;
  name: string;
  abbreviation: string;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `abbreviation` | `abbreviation` | string | Yes | Yes | No |
| `createdAt` | `created_at` | datetime | Auto | Yes | No |
| `updatedAt` | `updated_at` | datetime | Auto | Yes | No |

#### Example

```javascript
const payload = {
  data: {
    type: "units",
    attributes: {
      name: "Piece",
      abbreviation: "pcs"
    }
  }
};
```

---

## Common Use Cases

### 1. Product Catalog with Filters

```javascript
async function getProductCatalog(filters = {}) {
  const params = new URLSearchParams({
    'include': 'unit,category,brand',
    'sort': 'name',
    ...filters
  });

  const response = await fetch(`/api/v1/products?${params}`, { headers });
  return await response.json();
}

// Usage
const products = await getProductCatalog({
  'filter[category_id]': '5',
  'filter[search_name]': 'laptop'
});
```

### 2. Product Search by SKU

```javascript
async function findProductBySku(sku) {
  const response = await fetch(
    `/api/v1/products?filter[sku]=${sku}&include=unit`,
    { headers }
  );
  const data = await response.json();
  return data.data[0]; // Return first match
}
```

### 3. Create Product with Complete Data

```javascript
async function createProduct(productData) {
  const payload = {
    data: {
      type: "products",
      attributes: {
        name: productData.name,
        sku: productData.sku,
        description: productData.description,
        fullDescription: productData.fullDescription || null,
        price: parseFloat(productData.price),
        cost: parseFloat(productData.cost),
        iva: productData.iva === true,
        imgPath: productData.imgPath || null,
        datasheetPath: productData.datasheetPath || null,
        unitId: parseInt(productData.unitId),
        categoryId: productData.categoryId ? parseInt(productData.categoryId) : null,
        brandId: productData.brandId ? parseInt(productData.brandId) : null
      }
    }
  };

  const response = await fetch('/api/v1/products', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.errors[0].detail);
  }

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
| **Customer** | ✅ | ❌ | ❌ | ❌ |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/products` - List all products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get single product
- `PATCH /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product
- Same pattern for `/categories`, `/brands`, `/units`

**Related Modules:**
- [Inventory Module](INVENTORY_FRONTEND_GUIDE.md) - Stock management for products
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Sell products to customers
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Purchase products from suppliers
