# 📦 Product Module

Complete product catalog management system with units, brands, categories, and full JSON:API 5.x compliance.

## Features

- ✅ **Product Management** - Complete CRUD operations for products with advanced filtering
- ✅ **Brand Management** - Organize products by brands with slugs for SEO
- ✅ **Category Management** - Hierarchical product organization with slug support
- ✅ **Unit Management** - Flexible measurement units (weight, volume, length, pieces)
- ✅ **JSON:API 5.x Compliance** - Full specification compliance with proper pagination
- ✅ **Advanced Relationships** - Complete product associations with brands, categories, and units
- ✅ **Comprehensive Filtering** - Search by name, SKU, brand, category, unit
- ✅ **Pagination Support** - Efficient data loading with page-based pagination
- ✅ **Slug Support** - SEO-friendly URLs for brands and categories
- ✅ **Seeded Data** - Production-ready sample data for development and demos

## Data Structure

### Products
- `name` - Product name (required, sortable, filterable)
- `sku` - Stock Keeping Unit code (required, unique, sortable, filterable)
- `description` - Short product description
- `fullDescription` - Detailed product information
- `price` - Selling price (required, numeric, sortable)
- `cost` - Product cost (optional, numeric, sortable)
- `iva` - Tax applicable flag (boolean)
- `imgPath` - Product image path
- `datasheetPath` - Technical specification document path
- `createdAt` / `updatedAt` - Timestamps (camelCase for JSON:API)

### Brands
- `name` - Brand name (required, sortable, filterable)
- `slug` - SEO-friendly URL slug (required, sortable, filterable)
- `description` - Brand description
- `createdAt` / `updatedAt` - Timestamps

### Categories  
- `name` - Category name (required, sortable, filterable)
- `slug` - SEO-friendly URL slug (required, sortable, filterable)
- `description` - Category description
- `createdAt` / `updatedAt` - Timestamps

### Units
- `name` - Unit name (required, sortable, filterable)
- `code` - Unit code (required, unique, sortable, filterable)
- `unitType` - Unit type classification (weight, volume, length, unit)
- `createdAt` / `updatedAt` - Timestamps

## API Endpoints

All endpoints follow JSON:API 1.1 specification with consistent pagination:

```http
# Products
GET    /api/v1/products              # List products with pagination
POST   /api/v1/products              # Create new product
GET    /api/v1/products/{id}         # Get specific product
PATCH  /api/v1/products/{id}         # Update product
DELETE /api/v1/products/{id}         # Delete product

# Brands
GET    /api/v1/brands                # List brands with pagination
POST   /api/v1/brands                # Create new brand
GET    /api/v1/brands/{id}           # Get specific brand
PATCH  /api/v1/brands/{id}           # Update brand
DELETE /api/v1/brands/{id}           # Delete brand

# Categories
GET    /api/v1/categories            # List categories with pagination
POST   /api/v1/categories            # Create new category
GET    /api/v1/categories/{id}       # Get specific category
PATCH  /api/v1/categories/{id}       # Update category
DELETE /api/v1/categories/{id}       # Delete category

# Units
GET    /api/v1/units                 # List units with pagination
POST   /api/v1/units                 # Create new unit
GET    /api/v1/units/{id}            # Get specific unit
PATCH  /api/v1/units/{id}            # Update unit
DELETE /api/v1/units/{id}            # Delete unit
```

## Advanced Filtering & Pagination

### Pagination
```http
GET /api/v1/products?page[number]=1&page[size]=10
```

Response includes complete pagination metadata:
```json
{
  "data": [...],
  "meta": {
    "page": {
      "currentPage": 1,
      "from": 1,
      "lastPage": 5,
      "perPage": 10,
      "to": 10,
      "total": 50
    }
  },
  "links": {
    "first": "...",
    "last": "...",
    "next": "...",
    "prev": "..."
  }
}
```

### Filtering Examples
```http
# Filter products by name
GET /api/v1/products?filter[name]=iPhone

# Filter by brand and category
GET /api/v1/products?filter[brand_id]=1&filter[category_id]=2

# Filter brands by slug
GET /api/v1/brands?filter[slug]=apple

# Filter units by type
GET /api/v1/units?filter[unit_type]=weight
```

### Sorting
```http
# Sort products by price (descending)
GET /api/v1/products?sort=-price

# Sort by multiple fields
GET /api/v1/products?sort=name,price
```

### Relationships & Includes
```http
# Include related data
GET /api/v1/products?include=brand,category,unit

# Get brand with all products
GET /api/v1/brands/1?include=products
```

## Seeded Data

The module includes production-ready sample data:

### 🏷️ **Brands (12)**
- Apple, Samsung, Sony, LG, Huawei, Dell, HP, Lenovo, Microsoft, Google, Canon, Nikon

### 📂 **Categories (10)**  
- Smartphones, Laptops, Tablets, Televisores, Audífonos, Cámaras, Consolas, Smartwatches, Electrodomésticos, Accesorios

### 📏 **Units (8)**
- Weight: kg, g
- Volume: l, ml  
- Length: m, cm
- Count: pz (pieces), box

### 📱 **Products (8)**
Premium products with complete specifications:
- iPhone 15 Pro ($1,299.99)
- Samsung Galaxy S24 Ultra ($1,399.99)
- MacBook Pro 14" M3 ($2,199.99)
- Dell XPS 13 Plus ($1,699.99)
- Sony WH-1000XM5 ($399.99)
- LG C4 OLED 55" ($1,499.99)
- iPad Pro 12.9" M2 ($1,199.99)
- Canon EOS R6 Mark II ($2,499.99)

## Testing

```bash
# Run all Product module tests
php artisan test Modules/Product/

# Run specific test suites
php artisan test Modules/Product/tests/Feature/ProductIndexTest.php
php artisan test Modules/Product/tests/Feature/BrandIndexTest.php

# Run with pagination testing
php artisan test --filter=test_products_have_pagination
```

### Test Coverage
- **Products:** 20+ tests covering CRUD, relationships, pagination, filtering
- **Brands:** 15+ tests including slug validation and seeded data display
- **Categories:** 15+ tests with complete CRUD coverage
- **Units:** 15+ tests with unit type validation

## Usage Examples

### Creating a Product
```json
POST /api/v1/products
{
  "data": {
    "type": "products",
    "attributes": {
      "name": "New Smartphone",
      "sku": "NSM-2024-001",
      "description": "Latest smartphone with advanced features",
      "fullDescription": "Complete technical specifications...",
      "price": 899.99,
      "cost": 650.00,
      "iva": true,
      "imgPath": "/images/products/new-smartphone.jpg"
    },
    "relationships": {
      "brand": {
        "data": { "type": "brands", "id": "1" }
      },
      "category": {
        "data": { "type": "categories", "id": "1" }
      },
      "unit": {
        "data": { "type": "units", "id": "7" }
      }
    }
  }
}
```

### Response Format
```json
{
  "data": {
    "type": "products",
    "id": "9",
    "attributes": {
      "name": "New Smartphone",
      "sku": "NSM-2024-001",
      "description": "Latest smartphone with advanced features",
      "fullDescription": "Complete technical specifications...",
      "price": 899.99,
      "cost": 650.00,
      "iva": true,
      "imgPath": "/images/products/new-smartphone.jpg",
      "datasheetPath": null,
      "createdAt": "2025-08-08T10:30:00.000000Z",
      "updatedAt": "2025-08-08T10:30:00.000000Z"
    },
    "relationships": {
      "brand": {
        "data": { "type": "brands", "id": "1" },
        "links": {
          "self": "/api/v1/products/9/relationships/brand",
          "related": "/api/v1/products/9/brand"
        }
      },
      "category": {
        "data": { "type": "categories", "id": "1" },
        "links": {
          "self": "/api/v1/products/9/relationships/category", 
          "related": "/api/v1/products/9/category"
        }
      },
      "unit": {
        "data": { "type": "units", "id": "7" },
        "links": {
          "self": "/api/v1/products/9/relationships/unit",
          "related": "/api/v1/products/9/unit"
        }
      }
    },
    "links": {
      "self": "/api/v1/products/9"
    }
  }
}
```

## Permissions

Required permissions for operations:

### Products
- `products.index` - List products
- `products.show` - View product details
- `products.store` - Create new products
- `products.update` - Modify existing products
- `products.destroy` - Delete products

### Brands  
- `brands.index` - List brands
- `brands.show` - View brand details
- `brands.store` - Create new brands
- `brands.update` - Modify existing brands
- `brands.destroy` - Delete brands

### Categories
- `categories.index` - List categories
- `categories.show` - View category details
- `categories.store` - Create new categories
- `categories.update` - Modify existing categories
- `categories.destroy` - Delete categories

### Units
- `units.index` - List units
- `units.show` - View unit details
- `units.store` - Create new units
- `units.update` - Modify existing units
- `units.destroy` - Delete units

## Database Seeders

```bash
# Seed all Product module data
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder"

# Seed individual components
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\BrandSeeder"
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\CategorySeeder"
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\UnitSeeder"
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\ProductSeeder"
```

## Technical Notes

### JSON:API Compliance
- All responses follow JSON:API 1.1 specification
- Consistent field naming (camelCase in JSON, snake_case in database)
- Proper relationship handling with includes
- Standard pagination with meta and links
- Error responses follow JSON:API error format

### Performance Optimization
- Efficient pagination with `PagePagination::make()`
- Eager loading support for relationships
- Indexed database fields for common filters
- Optimized queries with proper field selection

### Consistency with Other Modules
- Aligned with Sales, Inventory, and Ecommerce modules
- Same pagination patterns and field naming conventions
- Consistent authorization and permission structure
- Standardized test patterns and coverage

---

**Module Version:** 2.0.0  
**JSON:API Compliance:** 5.x  
**Last Updated:** 2025-08-08
**Status:** Production Ready ✅