# API Base - Modular Laravel JSON:API

Este es un proyecto base en Laravel 12 con una arquitectura totalmente desacoplada y modular, ideal para construir APIs robustas siguiendo el estándar [JSON:API](https://jsonapi.org/). Está optimizado para proyectos a gran escala como ERPs, CRMs o sistemas administrativos que requieran control granular por módulos.

## Estado del Proyecto

**v1.0 RELEASE READY (2026-01-06)**

### Modulos Completos (14/14)

| Modulo | Entidades | Tests | Status |
|--------|-----------|-------|--------|
| **Product** | Products, Units, Categories, Brands, Variants | 26 | Completo |
| **Inventory** | Warehouses, Locations, Stock, Batches, Movements, CycleCounts | 24 | Completo |
| **Purchase** | Suppliers, Orders, Items, Approval, Budgets | 18 | Completo |
| **Sales** | Orders, Items, Shipments, Backorders, DiscountRules | 24 | Completo |
| **Ecommerce** | Carts, Checkout, Payments, Wishlists, Reviews, Recommendations | 64 | Completo |
| **Finance** | AP/AR Invoices, Payments, Bank Accounts, EarlyPaymentDiscount | 39 | Completo |
| **Accounting** | Accounts, Journal Entries, Fiscal Periods, Exchange Rates | 61 | Completo |
| **Reports** | Financial Statements, Management Reports, KPIs | 50 | Completo |
| **HR** | Employees, Attendance, Payroll, Leave, Performance | 45 | Completo |
| **CRM** | Pipeline, Leads, Campaigns, Activities, Opportunities | 25 | Completo |
| **Billing** | CFDI Invoices, PAC Integration (SW Sapien), Stripe | 25 | Completo |
| **Contacts** | Contacts, Addresses, Documents, Duplicate Detection | 20 | Completo |
| **Audit** | Activity Logs, Login History | 3 | Completo |
| **SystemHealth** | Health Checks, Metrics, Monitoring | 1 | Completo |

### Metricas

| Metrica | Valor |
|---------|-------|
| Modelos/Entidades | 85+ |
| Endpoints API | 736+ |
| Tests (archivos) | 452 |
| Reglas de negocio | 175/175 (100%) |
| Documentacion API | Scribe (664 endpoints) |

### Features v1.1 Implementados

- **PU-M003** Budget Control - Control presupuestal para OC
- **IV-M001** Cycle Count Scheduling - Conteos ciclicos de inventario
- **CO-M001** Duplicate Detection - Deteccion de contactos duplicados
- **SA-M003** Automatic Discount Rules - Reglas de descuento automaticas
- **FI-M002** Early Payment Discount - Descuentos por pronto pago
- **E2E Tests** - Tests de integracion Cart->Checkout->Order->Invoice

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
