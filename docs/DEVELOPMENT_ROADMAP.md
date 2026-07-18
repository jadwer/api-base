# Development Roadmap

**Last Updated:** 2026-07-18
**Status:** auditado 2026-07-17, ciclo transaccional refactorizado y verificado en dev
**Production Readiness:** NO afirmar porcentajes; ver "Correccion de la auditoria" abajo

---

## Correccion de la auditoria (2026-07-18)

Este documento decia "v1.0 RELEASE READY, Production Readiness 100%" y una tabla
con los 14 modulos en "Complete". La auditoria modular del 2026-07-17 probo que eso
era FALSO para el nucleo transaccional: las ventas no descontaban stock ni generaban
COGS, las cancelaciones no revertian nada, y los eventos que este roadmap daba por
funcionales (Order-to-Cash, Procure-to-Pay) no se disparaban en varios caminos. Los
tests E2E que el checklist marca como "passing" usaban Event::fake() y por eso nunca
lo detectaron.

El ciclo fue refactorizado y verificado end-to-end en dev el 2026-07-17 (commits
90ae514, 9a2b95d, 2581995, c23c5d1 y siguientes). Detalle completo, mapa de los 21
modulos y plan en `base/docs/audit-lwm-migration/` (carpeta del workspace, fuera de
este repo): SINTESIS_AUDITORIA_MODULAR.md, PLAN_REFACTOR_CICLO.md,
ROADMAP_POST_CUTOVER.md.

Se corrige con la verdad en vez de borrar para dejar constancia de que se audito,
que se encontro y por que cambio el estado.

## Estado Actual

### Modulos implementados (14) con estado auditado 2026-07

| Modulo | Entidades | Tests | Status auditado |
|--------|-----------|-------|-----------------|
| Product | Products, Units, Categories, Brands, Variants | 26 | Sano (ofertas y price/cost verificados) |
| Inventory | Warehouses, Locations, Stock, Batches, Movements, CycleCounts | 24 | Refactorizado 2026-07 (GL importe-0 y cuentas sin sembrar, corregido) |
| Purchase | Suppliers, Orders, Items, Approval Workflow, Budgets | 18 | Refactorizado 2026-07 (receive/cancel reales, reversa de stock) |
| Sales | Customers, Orders, Items, Shipments, Backorders, DiscountRules | 24 | Refactorizado 2026-07 (entrega descuenta stock + COGS, cancel revierte) |
| Ecommerce | Carts, Checkout, Payments, Wishlists, Reviews, Recommendations | 64 | Parcial (reserva de stock revertida, rediseno pendiente) |
| Finance | AP/AR Invoices, Payments, Bank Accounts, EarlyPaymentDiscount | 39 | Refactorizado 2026-07 (listener unico de facturacion, reversas) |
| Accounting | Accounts, Journal Entries, Fiscal Periods, Exchange Rates | 61 | Sano (motor de asientos verificado) |
| Reports | Financial Statements, Management Reports, KPIs | 50 | Sano |
| HR | Employees, Attendance, Payroll, Leave, Performance | 45 | A medias (CRUD sin motor de nomina) |
| CRM | Pipeline, Leads, Campaigns, Activities, Opportunities | 25 | A medias (CRUD sin motor) |
| Billing | CFDI Invoices, PAC Integration (SW Sapien), Stripe | 25 | Refactorizado 2026-07 (timbrado verificado en sandbox) |
| Contacts | Contacts, Addresses, Documents, Duplicate Detection | 20 | Sano |
| Audit | Activity Logging, Audit Trails | 3 | Sano |
| SystemHealth | Health Checks, Metrics | 1 | Sano |

### Metricas

| Metrica | Valor |
|---------|-------|
| Modelos/Entidades | 85+ |
| Endpoints API | 736+ |
| Tests (archivos) | 452, mayoria de fachada (rediseno por invariante en Fase 2.7) |
| Reglas de negocio | el "175/175 (100%)" anterior era falso; ver architecture/BUSINESS_RULES_COMPLETE.md corregido |
| Documentacion API | Scribe (664 endpoints) |

---

## Reglas de Negocio Implementadas

### P1 - Criticas (5/5 Complete)
- [x] **PU-A001** Purchase Approval Workflow
- [x] **IV-A001** Inventory GL Integration. Nota auditoria 2026-07: posteaba con
  importe 0 y las cuentas nunca se sembraron; corregido (total_value + chart
  1108/5101/2101)
- [x] **IV-A002** FEFO Batch Selection
- [ ] **SA-A001** Sales Reservation System. Nota auditoria 2026-07: la reserva
  nunca se convertia en salida; la reserva de ecommerce se REVIRTIO por
  descuadre estructural (ver PENDIENTE_REDISENO_RESERVAS.md en la auditoria)
- [x] **SA-A002** Line Calculation Engine

### P2 - Alta Prioridad (12/12 Complete)
- [x] **FI-M003** Credit Hold Automation
- [x] **IV-M002** Stock Reorder Alerts
- [x] **PU-M001** Three-Way Match
- [x] **IV-M003** Lot Traceability
- [x] **PR-M003** Product Variants
- [x] **SA-M001** Partial Shipments
- [x] **SA-M002** Backorder Management
- [x] **AC-M001** Period Close Checklist
- [x] **FI-M001** Late Payment Penalties
- [x] **Cross-Module** Event Listeners (Order-to-Cash, Procure-to-Pay). Nota
  auditoria 2026-07: varios eventos no se disparaban (cancelaciones, entrega);
  reparado en el refactor del ciclo
- [x] **Audit** Comprehensive activity logging (37 models)
- [x] **PU-M003** Budget Control for Purchase Orders

### P3 - v1.1 Features (6/6 Complete - 2026-01-06)
- [x] **IV-M001** Cycle Count Scheduling
- [x] **CO-M001** Duplicate Detection (Contacts)
- [x] **SA-M003** Automatic Discount Rules
- [x] **FI-M002** Early Payment Discount (pronto pago)
- [x] **E2E Tests** Online Sales Integration (Cart->Checkout->Order->Invoice).
  Nota auditoria 2026-07: usaban Event::fake(), no probaban el ciclo real; se
  reescriben por invariante en Fase 2.7
- [x] **Stripe** Payment Gateway Integration

### Integraciones Externas
- [x] **SW Sapien PAC** - CFDI stamping/cancellation
- [x] **Stripe** - Payment processing with gateway pattern
- [x] **Spatie Activity Log** - Audit trails

---

## Checklist Pre-Release v1.0

Nota 2026-07: este checklist se marco completo en enero pero la auditoria probo
que tres items no eran ciertos en la practica. Se anota el estado real en cada uno.

- [x] All modules implemented and tested (implementados si; "tested" era de fachada,
  Fase 2.7 pendiente)
- [x] PAC integration configured and tested (timbrado verificado en sandbox 2026-07)
- [x] Stripe integration configured and tested
- [x] Event listeners functional (Order-to-Cash, Procure-to-Pay): FALSO en enero,
  reparado en el refactor 2026-07
- [x] Audit logging active (37 models)
- [x] Database migrations consolidated
- [x] API documentation generated (Scribe - 664 endpoints)
- [x] E2E integration tests passing: pasaban con Event::fake(), no probaban el ciclo;
  reescritura en Fase 2.7
- [x] All v1.1 roadmap features implemented
- [ ] Production environment configured

---

## Archivos de Referencia

| Documento | Proposito |
|-----------|-----------|
| [DATABASE_SCHEMA_REFERENCE.md](DATABASE_SCHEMA_REFERENCE.md) | Schema de BD - LEER PRIMERO |
| [FRONTEND_INTEGRATION_GUIDE.md](FRONTEND_INTEGRATION_GUIDE.md) | Guia para frontend |
| [architecture/README.md](architecture/README.md) | Arquitectura del sistema |
| [architecture/BUSINESS_RULES_COMPLETE.md](architecture/BUSINESS_RULES_COMPLETE.md) | Inventario de reglas |
| [modules/](modules/) | Guias por modulo (17 archivos) |
| [ROADMAP_v1.1.md](ROADMAP_v1.1.md) | Features v1.1 (completado) |

### Archivados (Completados)
- `docs/archived/roadmaps-completed/AUDIT_IMPLEMENTATION_ROADMAP.md`
- `docs/archived/roadmaps-completed/MIGRATION_CONSOLIDATION_ROADMAP.md`

---

## Historial de Versiones

| Version | Fecha | Cambios |
|---------|-------|---------|
| v0.9 | 2025-12-30 | Migration consolidation, CRM complete |
| v0.95 | 2025-12-31 | All business rules P1+P2, Audit complete |
| v0.99 | 2026-01-02 | CRM-Contact integration, test fixes |
| v1.0-rc1 | 2026-01-03 | Test optimization (SQLite), PAC/Stripe tests |
| v1.0-rc2 | 2026-01-05 | PU-M003 Budget Control, Scribe API docs |
| v1.0 | 2026-01-06 | All v1.1 features, E2E tests, Stripe gateway |
| auditoria | 2026-07-17 | Auditoria modular: ciclo transaccional roto pese al "100%"; refactor ejecutado y verificado en dev |
| correccion | 2026-07-18 | Este doc corregido con el estado real (Fase 2.5, purga de confianza falsa) |

---

## Pendiente para v1.1+ (Futuro)

### Mejoras Opcionales
- [ ] PR-M001 Price History Tracking
- [ ] PR-M002 Bulk Price Updates
- [ ] CO-M002 Contact Segmentation
- [ ] PU-M002 Supplier Performance Tracking
- [ ] AC-M002 Budget vs Actual Tracking
- [ ] AC-M003 Multi-Currency Accounting

### Modulos Potenciales
- [ ] Notifications - Sistema centralizado (email, SMS, push)
- [ ] Documents - Gestion documental con versionado
- [ ] Workflows - Motor de workflows configurables

### Integraciones Futuras
- [ ] Facturama (alternativa PAC)
- [ ] Conekta/OpenPay (alternativas Stripe)
- [ ] WhatsApp Business API
