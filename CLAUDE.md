# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **modular Laravel 12 API** built with **JSON:API 5.x** specification, designed as a scalable base for enterprise applications like ERPs and CRMs. The project uses `nwidart/laravel-modules` for modular architecture with complete module isolation.

**Current Status:** 5 complete modules (Product, Inventory, Purchase, Sales, Ecommerce) with 543+ tests and JSON:API compliance.

## Architecture

### Tech Stack
- **Framework:** Laravel 12
- **API Standard:** JSON:API 5.x (strict compliance)
- **Modular System:** nwidart/laravel-modules
- **Authentication:** Laravel Sanctum
- **Authorization:** Spatie Laravel Permission (granular permissions)
- **Activity Logging:** Spatie Activity Log
- **Testing:** PHPUnit with JSON:API testing traits
- **Frontend Assets:** Vite + TailwindCSS 4.0

### Module Structure
Each module follows this standardized pattern:
```
Modules/{ModuleName}/
├── app/
│   ├── Http/Controllers/Api/V1/     # JSON:API Controllers with Actions traits
│   ├── JsonApi/V1/{Entities}/       # Schemas, Authorizers, Requests, Resources
│   ├── Models/                      # Eloquent models with relationships
│   └── Providers/                   # Service providers
├── Database/
│   ├── factories/                   # Model factories for testing
│   ├── migrations/                  # Database schema
│   └── seeders/                     # Data seeders and permissions
├── Tests/Feature/                   # Complete CRUD test suites
└── routes/jsonapi.php              # JSON:API route definitions
```

## Development Commands

### Core Operations
```bash
# Start development environment (runs server, queue, logs, vite concurrently)
composer dev

# Run specific module tests
php artisan test Modules/{ModuleName}/Tests/Feature/

# Run all tests
composer test

# Fresh install with seeded data
php artisan migrate:fresh --seed

# Generate API documentation
php artisan api:generate-docs
```

### Module Development
```bash
# Create new module
php artisan module:make {ModuleName}

# Generate complete module blueprint (advanced generator)
php artisan make:advanced-module-blueprint {ModuleName} --entities="Entity1,Entity2"
php artisan module:advanced-blueprint {ModuleName} --config="config.json"

# Force delete module with complete cleanup
php artisan module:force-delete {ModuleName}

# Run module-specific seeder
php artisan db:seed --class="Modules\\{ModuleName}\\Database\\Seeders\\{ModuleName}DatabaseSeeder"

# List all registered routes
php artisan route:list --path=api/v1

# Validate module structure
php artisan validate:module-structure {ModuleName}
```

### Asset Development
```bash
# Development mode (Vite)
npm run dev

# Build for production
npm run build
```

## Key Development Patterns

### JSON:API 5.x Implementation
- **Controllers:** Use Actions traits (FetchMany, FetchOne, Store, Update, Destroy)
- **Schemas:** Define fields, relationships, filters, and pagination in `fields()` method
- **Resources:** Map model attributes to JSON:API format
- **Authorizers:** Implement granular permission checking per endpoint
- **Requests:** Handle validation with JSON:API Rule helpers

### Authentication & Authorization
- All API endpoints require `auth:sanctum` middleware
- Permissions follow pattern: `{entities}.{action}` (plural, kebab-case)
- Role-based access: `god`, `admin`, `tech`, `customer` with granular permissions
- Authorizers check both roles and specific permissions

### Database Conventions
- Use `float` cast for decimal fields (better JSON:API compatibility)
- JSON fields use `ArrayHash` with associative arrays
- Foreign key constraints with `onDelete('restrict')` for data integrity
- Indexes on commonly filtered/sorted fields

### Testing Standards
- Minimum 5 test files per entity: Index, Show, Store, Update, Destroy
- Test all permission levels: admin, tech, customer, guest
- Validate JSON:API compliance using `->jsonApi()->expects()` helpers
- Use helper methods for user creation: `getAdminUser()`, `getTechUser()`

## Module Development Guidelines

### Creating New Modules
1. **Follow the Blueprint:** See `docs/development/module-blueprint-master.md` for complete specifications
2. **Use the Generator:** `php artisan make:advanced-module-blueprint` for consistent structure
3. **Entity Naming:** Singular for models/schemas, plural for endpoints/permissions
4. **Test-Driven:** Write tests for each CRUD operation before implementation
5. **Permission Setup:** Create granular permissions and assign to appropriate roles

### Common Pitfalls to Avoid
- **Namespace Errors:** Use `Modules\{ModuleName}\Http\Controllers` (without `/app/`)
- **Permission Naming:** Always use plural form (`warehouses.index`, not `warehouse.index`)
- **JSON Fields:** Use associative arrays for `ArrayHash` fields
- **Testing:** Don't use environment bypasses in Authorizers
- **Seeder Registration:** Always register module seeders in main `DatabaseSeeder`
- **User Model Guard:** Always set `protected $guard_name = 'api'` in User model for Spatie permissions
- **Factory Dependencies:** Validate existence of related models before creating records
- **Field Mapping:** Ensure camelCase (JSON:API) ↔ snake_case (database) consistency
- **Validation Types:** Use `numeric` for amounts, `sometimes` for PATCH updates

## API Usage

### Authentication
```bash
# Login to get token
POST /api/auth/login
{
  "email": "admin@example.com",
  "password": "password"
}

# Use token in subsequent requests
Authorization: Bearer {your-token}
```

### JSON:API Endpoints
All modules follow JSON:API 1.1 specification:
```bash
# List resources with filtering/sorting
GET /api/v1/{entities}?filter[name]=example&sort=name

# Get single resource with relationships
GET /api/v1/{entities}/{id}?include=relatedEntity

# Create resource
POST /api/v1/{entities}
Content-Type: application/vnd.api+json

# Update resource
PATCH /api/v1/{entities}/{id}
Content-Type: application/vnd.api+json
```

## Project Structure

### Completed Modules
- **Product:** Products, Units, Categories, Brands (20 routes, 71+ tests)
- **Inventory:** Warehouses, Locations, Stock, Batches (20 routes, 78+ tests)  
- **Purchase:** Suppliers, Orders, Items (15 routes, 141+ tests)
- **Sales:** Customers, Orders, Items (15 routes, 148+ tests)
- **Ecommerce:** Shopping Carts, Cart Items, Coupons (15 routes, 105+ tests)

### Core Configuration
- `app/JsonApi/V1/Server.php` - Central JSON:API server configuration
- `config/modules.php` - Module system configuration
- `phpunit.xml` - Test suite configuration with module support
- Database seeders create admin user: `admin@example.com` / `password`

## Documentation

### Available Documentation
- `docs/development/migration-roadmap.md` - Project migration status and metrics
- `docs/development/module-blueprint-master.md` - Complete module creation guide
- `docs/api/` - Auto-generated API documentation
- Module-specific README files in each module directory

### Generating Documentation
```bash
# Generate API docs for all modules
php artisan api:generate-docs

# Generate module-specific documentation
php artisan generate:module-documentation {ModuleName}
```

## Testing

### Test Execution
```bash
# Run all tests
php artisan test

# Run specific module tests
php artisan test Modules/{ModuleName}/Tests/

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter test_admin_can_create_entity
```

### Test Structure
Tests use clean patterns with pre-seeded users:
- `getAdminUser()` - Full permissions user
- `getTechUser()` - Read-only permissions  
- `getCustomerUser()` - Limited access user
- JSON:API testing helpers for consistent response validation

## Advanced Module Generator

### Command Usage
```bash
# Generate module from configuration file
php artisan module:advanced-blueprint {ModuleName} --config="config.json"

# Generate with inline entities
php artisan make:advanced-module-blueprint {ModuleName} --entities="Entity1,Entity2"

# Force regeneration (overwrite existing)
php artisan module:advanced-blueprint {ModuleName} --config="config.json" --force

# Force delete module with complete cleanup
php artisan module:force-delete {ModuleName}
```

### Generator Architecture (Refactored 2025-08-01)
The advanced module generator has been refactored from a single 3,830+ line "God class" into specialized components:

- **`CreateAdvancedModuleBlueprint.php`** - Main orchestrator command (450 lines)
- **`ModuleValidator.php`** - Entity name conflict detection and validation
- **`ConfigurationParser.php`** - JSON configuration parsing and validation  
- **`PermissionGenerator.php`** - Permission seeder generation with role mapping
- **`MigrationGenerator.php`** - Database migration generation with relationships
- **`SchemaGenerator.php`** - JSON:API schema generation with filtering/sorting
- **`TestGenerator.php`** - Comprehensive test suite generation (15 test files per module)
- **`IntegrationManager.php`** - Module integration and cleanup (Server.php, DatabaseSeeder.php, TestCase.php)

### JSON Configuration Format
```json
{
  "entities": {
    "EntityName": {
      "name": "EntityName",
      "tableName": "entity_names", 
      "fields": [
        {
          "name": "field_name",
          "type": "string|integer|boolean|decimal|date|text|json",
          "required": true|false,
          "fillable": true|false,
          "sortable": true|false,
          "filterable": true|false,
          "default": "value"
        }
      ]
    }
  },
  "relationships": [
    {
      "from": "EntityA",
      "to": "EntityB", 
      "type": "hasMany|belongsTo|hasOne|belongsToMany",
      "foreignKey": "entity_a_id"
    }
  ]
}
```

### Cross-Module Relationships
The generator automatically detects and imports models from other modules:
```php
// Automatically generated in models when referencing Product from other modules
use Modules\Product\Models\Product;

public function product()
{
    return $this->belongsTo(Product::class);
}
```

## Common Issues & Solutions

### Permission Problems
- Ensure permissions use plural naming: `customers.store` not `customer.store`
- Check role assignments in `AssignPermissionsSeeder`
- Verify Authorizer implements `LaravelJsonApi\Contracts\Auth\Authorizer`

### Module Registration Issues
- Register new modules in `app/JsonApi/V1/Server.php`
- Add seeder to main `DatabaseSeeder.php`
- Check `routes/jsonapi.php` registration in `RouteServiceProvider`

### Generator Issues (Fixed 2025-08-01)
- **Duplicate relationship methods**: Fixed relationship processing to prevent duplicate method names
- **Migration ordering**: Ensure foreign key dependencies are created before referenced tables
- **Factory constraints**: Match factory nullable fields with migration constraints
- **Schema import paths**: Use `Modules\{Module}\Models\{Model}` not `Modules\{Module}\app\Models\{Model}`
- **Cleanup failures**: IntegrationManager now uses simple string operations instead of complex regex

### Testing Failures
- Run `php artisan migrate:fresh --seed` before testing
- Check that test users exist with correct roles
- Verify JSON:API expects() calls match resource type names
- Test database may need refresh for cross-module relationship testing

### Module Deletion
Use the dedicated force-delete command for safe module removal:
```bash
php artisan module:force-delete {ModuleName}
```
This command safely removes:
- Module directory and all files
- Server.php schema and authorizer registrations
- DatabaseSeeder.php seeder entries
- TestCase.php module seeding calls
- modules_statuses.json entries
- Composer autoload cache

### Documentation Generator (Fixed 2025-08-08)
- **Missing fields issue**: Fixed regex pattern to capture all schema fields correctly
- **Incomplete parsing**: Added specific parsing of `fields()` method in schemas
- **Field name extraction**: Enhanced to handle complex field definitions with multiple parameters
- **Entity targeting**: Improved schema matching based on controller entity names
- **Complete coverage**: Now captures 12+ fields, relationships, and validations properly

### Product Module Consistency (Fixed 2025-08-08)
- **Pagination missing**: Added `PagePagination::make()` to all 4 schemas (Product, Brand, Category, Unit)
- **Field naming inconsistency**: Fixed snake_case/camelCase mixing - now consistent camelCase (createdAt, updatedAt)
- **Missing filters**: Added proper filtering support to all schemas with WhereIdIn and Where filters
- **JSON:API compliance**: All responses now include proper meta.page and links structure
- **Module alignment**: Product module now consistent with Sales, Inventory, and Ecommerce patterns

## CRITICAL DEVELOPMENT RULE

⚠️ **NEVER MAKE COMMITS AUTOMATICALLY** ⚠️

Claude Code MUST NEVER execute git commit commands automatically. Always provide only the commit text for the user to execute manually. This prevents loss of work and maintains user control over the git repository.

This modular architecture provides a scalable foundation for enterprise applications with complete JSON:API compliance, robust testing coverage, and comprehensive automated documentation generation. The advanced generator can replicate complex module structures with full relationship support across modules.