# Changelog

## [1.3.0] - 2025-08-23

### Added - Cross-Module Finance Integration (PRE-PHASE)
- **Sales Module Integration Fields**:
  - `ar_invoice_id` and `ar_invoice_status` in SalesOrder model
  - `gl_account_id`, `tax_account_id`, and `gl_posted` in SalesOrderItem model  
  - JSON:API schemas updated with new finance integration fields
- **Purchase Module Integration Fields**:
  - `ap_invoice_id` and `ap_invoice_status` in PurchaseOrder model
- **Inventory Module Integration Fields**:
  - Migration prepared for GL posting fields in inventory movements
- **Database Migrations**:
  - `2025_08_22_230213` - Finance integration fields for Sales tables
  - `2025_08_22_230243` - Finance integration fields for Purchase tables
  - `2025_08_22_230257` - GL integration fields for Inventory movements

### Changed
- **Module Architecture**: Prepared cross-module integration for automatic AR/AP invoice generation
- **Database Schema**: Added foreign key placeholders for future Finance/Accounting relationships

### Technical Preparation
- Foundation laid for Order-to-Cash workflow (Sales → AR Invoice → GL Posting)
- Foundation laid for Procure-to-Pay workflow (Purchase → AP Invoice → GL Posting)
- Foundation laid for Inventory Costing workflow (Movements → GL Adjustments)

## [1.2.0] - 2025-08-20

### Added - Finance & Accounting Phase 1
- **🏦 Finance Module**: Complete financial management system
  - **AP Invoices** (Accounts Payable) with calculated fields (`paidAmount`, `remainingBalance`)
  - **AR Invoices** (Accounts Receivable) with calculated fields (`paidAmount`, `remainingBalance`)
  - **AP/AR Payments & Receipts**: Direct payment/receipt tracking (Phase 1 simple model)
  - **Bank Accounts & Statements**: Banking integration framework
  - JSON:API compliant with full CRUD operations, filtering, sorting, and pagination
- **🧮 Accounting Module**: Chart of accounts and journal entry system
  - **Chart of Accounts** with hierarchical structure and account types
  - **Journal Entries & Lines**: Double-entry bookkeeping foundation
  - **Fiscal Periods**: Accounting period management
  - **Exchange Rates**: Multi-currency support framework
  - **5 Basic Accounts** pre-seeded: Banco, Clientes, Proveedores, Ingresos, Gastos
- **📋 Frontend Documentation**: Complete API specification
  - `FINANCE_ACCOUNTING_PHASE1_FRONTEND_REPORT.md` with endpoint documentation
  - Request/response examples with JSON:API format
  - Field validation rules and error handling
  - Authentication flow and testing examples

### Enhanced
- **Calculated Fields Implementation**: Real-time calculation of invoice balances
  - `paidAmount`: Sum of applied payments/receipts per invoice
  - `remainingBalance`: Automatic calculation (total - paidAmount)
  - Fields appear automatically in all JSON:API responses
- **JSON:API Compliance**: Full standard implementation across new modules
  - Pagination with `meta.page` and `links` structure
  - Advanced filtering by any field (`filter[field]=value`)
  - Sorting support (`sort=field,-other_field`)
  - Relationship inclusion (`?include=related`)
- **Spanish Validation Messages**: Localized error messages for better UX
- **Permission System**: Granular role-based access control
  - Finance permissions for god, admin, tech, and customer roles
  - Accounting permissions with appropriate restrictions

### Technical Improvements  
- **Database Integration**: Clean migration structure with foreign key constraints
- **Seeders Architecture**: Comprehensive data seeding with realistic test data
- **Model Relationships**: Proper Eloquent relationships with Phase 1 simplicity
- **Authorization**: Spatie permissions integration across all finance endpoints
- **Testing Ready**: All endpoints verified with curl and returning 200 OK

## [1.1.0] - 2025-08-14

### Added
- **Inventory Movement System**: Complete implementation of inventory movement tracking
  - `InventoryMovement` entity with 4 movement types (entry, exit, transfer, adjustment)
  - Comprehensive audit trail with previous/new stock tracking
  - Advanced validation business rules for transfers and movement types
  - Support for batch info and metadata JSON fields
  - Cross-warehouse transfer capabilities with destination tracking
  - 10+ comprehensive tests covering all CRUD operations and authorization
- **Enhanced Inventory Module**: Expanded from 4 to 5 entities
  - Added complete movement history tracking
  - Factory and seeder with realistic movement data
  - Granular permissions for tech role (read-only inventory access)
- **Documentation Updates**: 
  - Updated README with new entity count (18 entities, 553+ tests)
  - Added movement system documentation

### Technical Improvements
- **JSON:API Schema Patterns**: Refined BelongsTo relationship definitions
- **Permission System**: Enhanced role-based authorization for inventory operations
- **Test Patterns**: Improved test reliability with better user permission management

## [1.0.0] - 2025-08-01

### Added
- **Advanced Module Generator System**: Complete refactoring of module generation system
  - `php artisan module:advanced-blueprint` command with JSON configuration support
  - `php artisan module:force-delete` command for safe module removal
  - Cross-module relationship detection and automatic model imports
  - 7 specialized generator classes following Single Responsibility Principle
- **Architecture Refactoring**: Broke down 3,830+ line "God class" into manageable components:
  - `ModuleValidator.php` - Entity name conflict detection
  - `ConfigurationParser.php` - JSON configuration parsing
  - `PermissionGenerator.php` - Permission seeder generation
  - `MigrationGenerator.php` - Database migration generation
  - `SchemaGenerator.php` - JSON:API schema generation
  - `TestGenerator.php` - Comprehensive test suite generation
  - `IntegrationManager.php` - Module integration and cleanup
- **Complete Module System**: 4 production-ready modules with 438+ tests
  - **Product Module**: Products, Units, Categories, Brands (71+ tests)
  - **Inventory Module**: Warehouses, Locations, Stock, Batches (78+ tests)
  - **Purchase Module**: Suppliers, Orders, Items (141+ tests)  
  - **Sales Module**: Customers, Orders, Items (148+ tests)
- **Ecommerce Module**: Shopping carts, cart items, coupons with full JSON:API compliance
- **Enhanced Documentation**: Updated CLAUDE.md with complete generator usage and troubleshooting

### Fixed
- **Critical Generator Bugs**:
  - Duplicate relationship methods in generated models
  - Migration ordering issues causing foreign key failures
  - Factory constraints mismatching migration requirements
  - Schema import path bugs (`app\Models` vs `Models`)
  - Cleanup failures in Server.php, DatabaseSeeder.php, TestCase.php
- **Relationship Processing**: Fixed support for both `from/to` and `entityA/entityB` formats
- **Windows Compatibility**: Enhanced deletion methods with retry mechanisms and proper error handling
- **Permission System**: Fixed permission assignment and role mapping in generated modules

### Changed
- **Generator Architecture**: Completely refactored from monolithic to modular design
- **Command Interface**: Simplified with better error handling and progress feedback
- **Integration System**: More robust cleanup and validation mechanisms
- **Documentation**: Comprehensive update with new patterns and troubleshooting guide

## [0.1.0] - 2025-06-17

### Added
- Base del proyecto `api-base` con Laravel 12 y arquitectura modular (`nwidart/laravel-modules`)
- Configuración de composer con `merge-plugin` para módulos individuales
- Integración de `laravel-json-api/laravel:^5.1` para estructura JSON:API
- Módulo `User`:
  - Modelo `User` con SoftDeletes, HasFactory, roles y auditoría con `spatie/laravel-activitylog`
  - Migración con ampliación del esquema base para añadir `status`
  - Seeder de roles y permisos (`god`, `admin`, `customer`, `guest`)
  - Seeder de usuarios usando `Factory` con contraseñas encriptadas (`supersecure`)
  - Estructura JSON:API completa (Schema, Request, Resource, Controller)
  - Validaciones con `UserRequest` (con pruebas en bash)
- Módulo `Auth`:
  - Endpoints `/api/auth/login` y `/api/auth/logout`
  - Login vía Sanctum
  - Logout funcional con token

### Fixed
- Problemas con PSR-4 resueltos ajustando `composer.json`
- Factory personalizada reconocida correctamente para modelo modular
- Error por tabla `activity_log` inexistente, corregido con migración pendiente

### Removed
- Rutas web innecesarias de módulos generados por defecto
