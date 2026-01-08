# Product Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Product | `/api/v1/products` | Product catalog |
| Category | `/api/v1/categories` | Product categories |
| Brand | `/api/v1/brands` | Product brands |
| Unit | `/api/v1/units` | Units of measure |
| ProductVariant | `/api/v1/product-variants` | Product variants (size, color) |

## Product

```typescript
interface Product {
  id: string;
  name: string;
  sku: string;           // Unique
  description: string | null;
  price: number;
  cost: number;
  iva: number;           // Default 16%
  isActive: boolean;
  unitId: number;
  categoryId: number | null;
  brandId: number | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

### CRUD Operations

```typescript
// List products
GET /api/v1/products
GET /api/v1/products?filter[is_active]=true&include=category,brand

// Get product
GET /api/v1/products/{id}?include=unit,category,brand,variants

// Create product
POST /api/v1/products
{
  "data": {
    "type": "products",
    "attributes": {
      "name": "iPhone 15 Pro",
      "sku": "APL-IPH15P-256",
      "price": 1299.99,
      "cost": 999.00,
      "iva": 16,
      "isActive": true,
      "unitId": 1,
      "categoryId": 5,
      "brandId": 3
    }
  }
}

// Update product
PATCH /api/v1/products/{id}
{
  "data": {
    "type": "products",
    "id": "1",
    "attributes": {
      "price": 1199.99
    }
  }
}

// Delete product
DELETE /api/v1/products/{id}
```

### Filters

| Filter | Example |
|--------|---------|
| `filter[name]` | `?filter[name]=iPhone` |
| `filter[sku]` | `?filter[sku]=APL-IPH15P` |
| `filter[is_active]` | `?filter[is_active]=true` |
| `filter[category_id]` | `?filter[category_id]=5` |
| `filter[brand_id]` | `?filter[brand_id]=3` |

### Sort Options
`?sort=name`, `?sort=-price`, `?sort=sku`

## Category

```typescript
interface Category {
  id: string;
  name: string;
  slug: string;          // Unique, URL-friendly
  description: string | null;
  parentId: number | null;  // For hierarchy
  isActive: boolean;
}

// List categories with hierarchy
GET /api/v1/categories?include=parent,children

// Create category
POST /api/v1/categories
{
  "data": {
    "type": "categories",
    "attributes": {
      "name": "Smartphones",
      "slug": "smartphones",
      "parentId": 1,
      "isActive": true
    }
  }
}
```

## Brand

```typescript
interface Brand {
  id: string;
  name: string;
  slug: string;          // Unique
  description: string | null;
  isActive: boolean;
}

// List brands
GET /api/v1/brands?filter[is_active]=true

// Create brand
POST /api/v1/brands
{
  "data": {
    "type": "brands",
    "attributes": {
      "name": "Apple",
      "slug": "apple",
      "isActive": true
    }
  }
}
```

## Unit

```typescript
interface Unit {
  id: string;
  name: string;
  abbreviation: string;  // PZA, KG, LT
  isActive: boolean;
}

// List units
GET /api/v1/units
```

## Product Variants

```typescript
interface ProductVariant {
  id: string;
  productId: number;
  sku: string;
  name: string;
  attributes: Record<string, string>;  // { "color": "Black", "size": "256GB" }
  price: number | null;                 // Override parent price
  isActive: boolean;
}

// List variants for product
GET /api/v1/product-variants?filter[product_id]=1

// Create variant
POST /api/v1/product-variants
{
  "data": {
    "type": "product-variants",
    "attributes": {
      "productId": 1,
      "sku": "APL-IPH15P-256-BLK",
      "name": "iPhone 15 Pro 256GB Black",
      "attributes": { "color": "Black", "storage": "256GB" },
      "price": 1299.99,
      "isActive": true
    }
  }
}
```

## Public Product API (No Auth)

```typescript
// Public catalog (for ecommerce storefront)
GET /api/public/v1/public-products
GET /api/public/v1/public-products/{id}

// Only returns active products
```

## Business Rules

| Rule | Description |
|------|-------------|
| SKU Uniqueness | SKU must be unique across all products |
| Price >= 0 | Price and cost must be non-negative |
| IVA Range | IVA must be between 0-100% (default 16%) |
| Active Filter | Public API only shows is_active=true |
| Category Hierarchy | Categories support parent-child nesting |
