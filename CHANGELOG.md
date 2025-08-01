# Changelog

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
