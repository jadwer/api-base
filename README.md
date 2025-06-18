# API Base - Modular Laravel JSON:API

Este es un proyecto base en Laravel 12 con una arquitectura totalmente desacoplada y modular, ideal para construir APIs robustas siguiendo el estándar [JSON:API](https://jsonapi.org/). Está optimizado para proyectos a gran escala como ERPs, CRMs o sistemas administrativos que requieran control granular por módulos.

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
