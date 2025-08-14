# API Base - Modular Laravel JSON:API

Este es un proyecto base en Laravel 12 con una arquitectura totalmente desacoplada y modular, ideal para construir APIs robustas siguiendo el estándar [JSON:API](https://jsonapi.org/). Está optimizado para proyectos a gran escala como ERPs, CRMs o sistemas administrativos que requieran control granular por módulos.

## 🎯 Estado del Proyecto

**✅ 5 Módulos Completados:**
- **Product Module**: 4 entidades, 71+ tests ✅
- **Inventory Module**: 5 entidades, 88+ tests ✅ (incluye InventoryMovement)
- **Purchase Module**: 3 entidades, 141+ tests ✅
- **Sales Module**: 3 entidades, 148+ tests ✅
- **Ecommerce Module**: 3 entidades, 105+ tests ✅

**Total: 18 entidades con 553+ tests funcionando**

## Autor

**DCC Rodrigo Gabino Ramírez Moreno**  
Email: gabino.ramirez.moreno@gmail.com  
Repositorio: privado

## Características principales

- Laravel 12 con `nwidart/laravel-modules`
- Soporte completo para JSON:API con `laravel-json-api/laravel:^5.1`
- Autenticación con Sanctum (`/api/auth/login`, `/api/auth/logout`)
- Sistema de roles y permisos (`spatie/laravel-permission`)
- Auditoría con `spatie/laravel-activitylog`
- Estructura escalable y limpia para nuevos módulos
- **Documentación automática** de API con `php artisan api:generate-docs`

## 📚 Documentación

Toda la documentación del proyecto está organizada en la carpeta [`docs/`](./docs/):

- **API**: Documentación completa de endpoints → [`docs/api/`](./docs/api/)
- **Desarrollo**: Blueprints y roadmaps → [`docs/development/`](./docs/development/)
- **Arquitectura**: Visión general del sistema → [`docs/architecture/`](./docs/architecture/)

### Generar Documentación de API

```bash
php artisan api:generate-docs
```

## Instalación

```bash
git clone <tu-repo>
cd api-base
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan module:seed User
php artisan serve
