# Changelog

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
