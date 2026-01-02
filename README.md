# API Base - Modular Laravel JSON:API

Este es un proyecto base en Laravel 12 con una arquitectura totalmente desacoplada y modular, ideal para construir APIs robustas siguiendo el estándar [JSON:API](https://jsonapi.org/). Está optimizado para proyectos a gran escala como ERPs, CRMs o sistemas administrativos que requieran control granular por módulos.

## 🎯 Estado del Proyecto

**✅ v1.0 RELEASE READY - 13 Módulos Completos:**

| Módulo | Entidades | Tests | Status |
|--------|-----------|-------|--------|
| **Product** | Products, Units, Categories, Brands | 71+ | ✅ |
| **Inventory** | Warehouses, Locations, Stock, Batches, Movements | 88+ | ✅ |
| **Purchase** | Suppliers, Orders, Items + Approval | 141+ | ✅ |
| **Sales** | Customers, Orders, Items + Tracking + Shipments | 201+ | ✅ |
| **Ecommerce** | Carts, Checkout, Payments, Wishlists, Reviews | 237+ | ✅ |
| **Finance** | AP/AR Invoices, Payments, Bank Accounts | 200+ | ✅ |
| **Accounting** | Accounts, Journal Entries, Fiscal Periods | 150+ | ✅ |
| **Reports** | Financial Statements, Management Reports | 50+ | ✅ |
| **HR** | Employees, Attendance, Payroll, Leave, Reviews | 400+ | ✅ |
| **CRM** | Pipeline, Leads, Campaigns, Activities, Opportunities | 250+ | ✅ |
| **Billing** | CFDI Invoices, PAC Integration (SW), XML/PDF | 50+ | ✅ |
| **Audit** | Activity Logs, Login History | 30+ | ✅ |
| **SystemHealth** | Health Checks, Metrics, Monitoring | 20+ | ✅ |

**Total: 65+ entidades, 320+ endpoints, 3,500+ tests**

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
