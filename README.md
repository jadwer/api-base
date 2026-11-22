# API Base - Modular Laravel JSON:API

Este es un proyecto base en Laravel 12 con una arquitectura totalmente desacoplada y modular, ideal para construir APIs robustas siguiendo el estándar [JSON:API](https://jsonapi.org/). Está optimizado para proyectos a gran escala como ERPs, CRMs o sistemas administrativos que requieran control granular por módulos.

## Estado del Proyecto

Plantilla del ERP en produccion (LaborWasser y AlphaLab). 23 modulos, 1,002 rutas de API y
603 archivos de prueba. Lo comercial (catalogo, tienda, cotizaciones, pedidos, compras,
inventario) opera; facturacion, finanzas y contabilidad existen pero no se han usado con
datos reales. Estado, rumbo y pendientes: `CLAUDE.md` y el `ROADMAP.md` del workspace.

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

## Documentación

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
