# Public Catalog JSON:API Refactor - Roadmap

## Context
The current `CatalogController` implementation breaks the established JSON:API architecture pattern used throughout the project. This document outlines the proper refactoring approach to maintain architectural consistency while providing public catalog access.

## Current State
- ✅ Working but non-compliant `CatalogController` with custom JSON responses
- ✅ Public routes defined in `catalog.php`
- ❌ Not following JSON:API 5.x specification
- ❌ Inconsistent with existing Product, Sales, Inventory modules

## Target Architecture
Create a JSON:API compliant public catalog system using:
- **PublicProductSchema** - JSON:API schema with proper field mappings
- **PublicProductAuthorizer** - Authorizer allowing guest/unregistered access
- **PublicProductController** - Controller using JSON:API Actions traits
- **Public JSON:API routes** - Proper route registration in Server.php

## Roadmap

### Phase 1: Schema & Authorization
1. **Create PublicProductSchema**
   - Extend base ProductSchema or create new schema
   - Map all Product model fields (sku, name, description, etc.)
   - Include relationships (unit, category, brand)
   - Add proper filtering and sorting capabilities
   - Implement PagePagination

2. **Create PublicProductAuthorizer**
   - Allow unrestricted access for index/show operations
   - Block create/update/delete operations
   - No authentication required

### Phase 2: Controller & Routes
3. **Create PublicProductController**
   - Use FetchMany and FetchOne action traits only
   - Remove Store, Update, Destroy capabilities
   - Follow existing controller patterns

4. **Configure JSON:API Routes**
   - Register in `app/JsonApi/V1/Server.php`
   - Create public route group without auth middleware
   - Maintain `/api/public/v1/` prefix pattern

### Phase 3: Integration & Testing
5. **Update Route Registration**
   - Modify Product module RouteServiceProvider
   - Ensure proper middleware configuration
   - Remove old catalog.php routes

6. **Test JSON:API Compliance**
   - Verify proper Content-Type headers
   - Test filtering: `?filter[category]=electronics`
   - Test sorting: `?sort=name,-createdAt`
   - Test inclusion: `?include=category,brand,unit`
   - Test pagination: `?page[number]=2&page[size]=10`

## Implementation Benefits
- ✅ Maintains JSON:API 5.x compliance
- ✅ Consistent with existing module architecture
- ✅ Proper filtering, sorting, and relationship inclusion
- ✅ Standard pagination with meta/links structure
- ✅ Extensible for future public entities (categories, brands)

## Todo List for Implementation

### Core Components
- [ ] Create `Modules/Product/app/JsonApi/V1/PublicProducts/PublicProductSchema.php`
- [ ] Create `Modules/Product/app/JsonApi/V1/PublicProducts/PublicProductAuthorizer.php`
- [ ] Create `Modules/Product/app/JsonApi/V1/PublicProducts/PublicProductResource.php`
- [ ] Create `Modules/Product/app/JsonApi/V1/PublicProducts/PublicProductRequest.php`
- [ ] Create `Modules/Product/app/Http/Controllers/Api/V1/PublicProductController.php`

### Configuration
- [ ] Register PublicProduct schema in `app/JsonApi/V1/Server.php`
- [ ] Create public routes in `routes/jsonapi.php` or separate public routes file
- [ ] Update RouteServiceProvider to handle public JSON:API routes
- [ ] Remove old CatalogController and catalog.php routes

### Testing
- [ ] Create comprehensive test suite for public endpoints
- [ ] Test guest access (no authentication)
- [ ] Test JSON:API compliance (headers, structure, meta)
- [ ] Test filtering by category, brand, search terms
- [ ] Test sorting capabilities
- [ ] Test relationship inclusion
- [ ] Test pagination functionality

## Context Prompt for Next Session

```
Contexto: Necesitamos refactorizar el sistema de catálogo público de productos para que siga la especificación JSON:API 5.x establecida en el proyecto.

Estado actual:
- Existe un CatalogController que funciona pero NO sigue JSON:API
- El proyecto usa arquitectura modular con nwidart/laravel-modules
- Todos los módulos (Product, Sales, Inventory, Contacts) siguen JSON:API 5.x
- Se requiere acceso público (sin autenticación) al catálogo de productos

Objetivo:
Crear sistema de catálogo público que mantenga coherencia arquitectónica:
1. PublicProductSchema con JSON:API compliance
2. PublicProductAuthorizer permitiendo acceso guest
3. PublicProductController con Actions traits (FetchMany, FetchOne únicamente)
4. Rutas JSON:API públicas registradas correctamente
5. Tests completos verificando JSON:API compliance

Arquitectura del proyecto:
- Laravel 12 + JSON:API 5.x
- Modular con Modules/Product/app/JsonApi/V1/{Entity}/
- Authorizers en cada schema para permisos granulares
- Controllers usan Actions traits (FetchMany, FetchOne, Store, Update, Destroy)
- Testing con ->jsonApi()->expects() helpers

Lee el archivo docs/development/public-catalog-json-api-refactor.md para el roadmap completo.

Tarea: Implementar el sistema de catálogo público siguiendo la especificación JSON:API del proyecto.
```

## File Locations
- **Roadmap**: `docs/development/public-catalog-json-api-refactor.md` (this file)
- **Original working catalog**: `Modules/Product/app/Http/Controllers/Api/V1/CatalogController.php`
- **Product Schema reference**: `Modules/Product/app/JsonApi/V1/Products/ProductSchema.php`
- **Product Controller reference**: `Modules/Product/app/Http/Controllers/Api/V1/ProductController.php`

---
*Created: 2025-08-18 - For proper JSON:API public catalog implementation*