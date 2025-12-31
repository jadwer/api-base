# Development Roadmap 2025 - Final Sprint

**Last Updated:** 2025-12-31
**Status:** 🏁 **FINAL SPRINT TO v1.0 RELEASE**
**Production Readiness:** 96% → Target 98%

---

## 📊 Estado Actual del Proyecto

### Módulos Completados (11/11 - 100%)
| Módulo | Entidades | Tests | Status |
|--------|-----------|-------|--------|
| **Product** | Products, Units, Categories, Brands | 71+ | ✅ |
| **Inventory** | Warehouses, Locations, Stock, Batches, Movements | 88+ | ✅ |
| **Purchase** | Suppliers, Orders, Items + Approval | 141+ | ✅ |
| **Sales** | Customers, Orders, Items + Tracking + **Shipments** | 201+ | ✅ |
| **Ecommerce** | Carts, Checkout, Payments, Wishlists, Reviews | 237+ | ✅ |
| **Finance** | AP/AR Invoices, Payments, Bank Accounts, **ARPayment** | 200+ | ✅ |
| **Accounting** | Accounts, Journal Entries, Fiscal Periods | 150+ | ✅ |
| **Reports** | Financial Statements, Management Reports | 50+ | ✅ |
| **HR** | Employees, Attendance, Payroll, Leave, Performance | 400+ | ✅ |
| **CRM** | Pipeline, Leads, Campaigns, Activities, Opportunities | 250+ | ✅ |
| **Billing** | CFDI Invoices, PAC Integration (SW), XML/PDF | 50+ | ⚙️ Config pendiente |

### Métricas de Implementación
- **Entidades totales:** 55+
- **Endpoints API:** 260+
- **Tests:** 3,100+ (3096 pasando)
- **Reglas de negocio:** 150/175 implementadas (85.7%)
- **Production Readiness:** 90%

---

## 🎯 ROADMAP FINAL PARA v1.0

### FASE A: Configuración Billing ✅ COMPLETADA
**Tiempo estimado:** 1-2 horas (solo configuración)
**Completado:** 2025-12-31

| Tarea | Descripción | Status |
|-------|-------------|--------|
| A.1 | Configurar credenciales SW Sapien en `.env` | ✅ Token configurado |
| A.2 | Subir certificados CSD (.cer, .key) a `storage/certificates/` | ✅ xiqb891116qe4.cer/.key |
| A.3 | Configurar datos fiscales en `company_settings` | ⏳ Pendiente (no blocker) |
| A.4 | Configurar Stripe API keys (si aplica) | ✅ pk_test/sk_test configurados |
| A.5 | Probar timbrado en sandbox SW | ✅ Conexión verificada |

**Archivos `.env` requeridos:**
```env
# SW Sapien PAC
SW_PAC_ENABLED=true
SW_PAC_URL=https://services.test.sw.com.mx
SW_PAC_TOKEN=xxx  # O usar SW_PAC_USER + SW_PAC_PASSWORD

# Certificados CSD
CFDI_CER_PATH=storage/certificates/csd.cer
CFDI_KEY_PATH=storage/certificates/csd.key
CFDI_KEY_PASSWORD=xxx

# Stripe (opcional)
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

---

### FASE B: Reglas de Negocio Críticas ✅ COMPLETADA
**Tiempo estimado:** 10 horas
**Impacto:** Production Readiness 90% → 94%
**Completado:** 2025-12-31

| ID | Regla | Módulo | Horas | Status |
|----|-------|--------|-------|--------|
| **FI-M003** | Credit Hold Automation | Finance | 2h | ✅ Implementado |
| **IV-M002** | Stock Reorder Alerts | Inventory | 2h | ✅ Implementado |
| **PU-M001** | Three-Way Match (PO vs Receipt vs Invoice) | Purchase | 6h | ✅ Implementado |

**Comandos verificados:**
- `php artisan finance:check-overdue` - Scheduled daily
- `php artisan inventory:check-reorder-alerts` - Scheduled daily
- `APInvoiceReconciliationService::reconcileInvoice()` - On-demand

**Detalle de implementación:**

#### FI-M003: Credit Hold Automation
```php
// Scheduled command: CheckOverdueInvoices
// Si customer tiene facturas vencidas > 60 días:
// - Set contact.credit_status = 'on_hold'
// - Block new sales orders for this customer
// - Send notification to sales manager
```

#### IV-M002: Stock Reorder Alerts
```php
// Scheduled command: CheckReorderAlerts
// Si stock.quantity <= product.reorder_point:
// - Create notification for purchasing
// - Optional: Auto-create purchase order draft
```

#### PU-M001: Three-Way Match
```php
// Service: ThreeWayMatchService
// Compara: PO.quantity vs Receipt.quantity vs APInvoice.quantity
// Tolerance: Qty ±1%, Price ±5%, Amount $0.50
// Si discrepancia: Require approval before payment
```

---

### FASE C: Event Listeners Cross-Module ✅ COMPLETADA
**Tiempo estimado:** 4 horas
**Impacto:** Habilita automatización Order-to-Cash y Procure-to-Pay
**Completado:** 2025-12-31

| Tarea | Descripción | Horas | Status |
|-------|-------------|-------|--------|
| C.1 | Implementar SalesOrderCompletedListener | 1.5h | ✅ Ya implementado |
| C.2 | Implementar PurchaseOrderReceivedListener | 1.5h | ✅ Ya implementado |
| C.3 | Verificar EventDrivenIntegrationTest | 1h | ✅ 5/5 tests pasando |

**Flujo automatizado resultante:**
```
Sales Order Completed
    → Create AR Invoice (automático)
    → Post to GL (automático)
    → Update sales_order.ar_invoice_id

Purchase Order Received
    → Create Inventory Movement (automático)
    → Create AP Invoice (automático)
    → Post to GL (automático)
```

---

### FASE D: Reglas de Negocio Alta Prioridad 🟠
**Tiempo estimado:** 14 horas
**Impacto:** Production Readiness 94% → 96%

| ID | Regla | Módulo | Horas | Impacto |
|----|-------|--------|-------|---------|
| **IV-M003** | Lot Traceability | Inventory | 6h | Compliance (food/pharma) |
| **PR-M003** | Product Variants | Product | 8h | E-commerce crítico |

---

### FASE E: Reglas de Negocio Media Prioridad 🟢
**Tiempo estimado:** 19 horas
**Impacto:** Production Readiness 96% → 98%

| ID | Regla | Módulo | Horas | Status |
|----|-------|--------|-------|--------|
| SA-M001 | Partial Shipment Support | Sales | 6h | ✅ Completado |
| SA-M002 | Backorder Management | Sales | 5h | ✅ Completado |
| AC-M001 | Period Close Checklist | Accounting | 4h | Pendiente |
| FI-M001 | Late Payment Penalties | Finance | 4h | Pendiente |

**SA-M001 Implementación (2025-12-31):**
- Modelos: Shipment, ShipmentItem
- Migraciones: shipments, shipment_items, add_shipment_fields_to_sales_order_items
- Service: ShipmentService (createShipment, markAsShipped, markAsDelivered, cancelShipment)
- API Endpoints: CRUD + /ship, /deliver, /cancel, /shipment-summary
- Tests: 53 tests (Index, Show, Store, Update, Destroy, ServiceTest)

**SA-M002 Implementación (2025-12-31):**
- Modelo: Backorder (status: pending, partially_fulfilled, fulfilled, cancelled; priority: low, normal, high, urgent)
- Migración: backorders table con tracking de cantidades y fechas
- Service: BackorderService (createBackorder, fulfillBackorder, cancelBackorder, fulfillForProduct, getPendingBackordersForProduct)
- API Endpoints: CRUD + /fulfill, /cancel, /fulfill-for-product, /pending-for-product/{productId}, /backorder-summary
- Features: Auto-generación de backorder_number, priorización por urgencia, cumplimiento automático al recibir stock
- Tests: 50+ tests (Index, Show, Store, Update, Destroy, ServiceTest)

---

### FASE F: Cleanup y Documentación 📚
**Tiempo estimado:** 4 horas

| Tarea | Descripción | Horas |
|-------|-------------|-------|
| F.1 | Fix PaymentApplication relationship (no refactor, solo clarificar) | 0.5h |
| F.2 | Add SalesOrder.subtotal column | 0.5h |
| F.3 | Fix Campaign UpdateTest (27 failures) | 1h |
| F.4 | Generate API documentation con Scribe | 1h |
| F.5 | Update FRONTEND_INTEGRATION_GUIDE con nuevos endpoints | 1h |

---

## 📋 RESUMEN POR PRIORIDAD

### 🔴 CRÍTICO (Must Have para v1.0)
| Fase | Tareas | Horas | Dependencias |
|------|--------|-------|--------------|
| **A** | Billing Configuration | 2h | Credenciales SW + CSD |
| **B** | Credit Hold + Reorder Alerts + Three-Way Match | 10h | Ninguna |
| **C** | Event Listeners | 4h | Ninguna |

**Subtotal Crítico:** 16 horas

### 🟡 ALTA (Should Have)
| Fase | Tareas | Horas |
|------|--------|-------|
| **D** | Lot Traceability + Product Variants | 14h |

**Subtotal Alta:** 14 horas

### 🟢 MEDIA (Nice to Have)
| Fase | Tareas | Horas |
|------|--------|-------|
| **E** | Partial Shipment, Backorder, Period Close, Late Penalties | 19h |
| **F** | Cleanup y Documentación | 4h |

**Subtotal Media:** 23 horas

---

## ⏱️ TIMELINE ESTIMADO

```
SEMANA 1 (Hoy - 7 días)
├── Día 1-2: Fase A - Configuración Billing (con credenciales)
├── Día 2-3: Fase B.1-B.2 - Credit Hold + Reorder Alerts (4h)
├── Día 4-5: Fase B.3 - Three-Way Match (6h)
└── Día 6-7: Fase C - Event Listeners (4h)

SEMANA 2
├── Día 8-10: Fase D - Lot Traceability (6h)
├── Día 11-14: Fase D - Product Variants (8h)
└── Buffer para testing

SEMANA 3 (Opcional)
├── Fase E - Medium priority rules
└── Fase F - Cleanup y documentación

🎉 v1.0 RELEASE: Fin de Semana 2
```

---

## 📊 Production Readiness Progression

| Milestone | Readiness | Fase |
|-----------|-----------|------|
| **Actual** | 95% | A+B+C ✅ |
| ~~Post Billing Config~~ | ~~92%~~ | ~~A~~ ✅ |
| ~~Post Critical Rules~~ | ~~94%~~ | ~~B~~ ✅ |
| ~~Post Event Listeners~~ | ~~95%~~ | ~~C~~ ✅ |
| **Post High Priority** | 96% | D |
| **v1.0 Release Target** | **98%** | E+F |

---

## 🔧 REGLAS DE NEGOCIO - STATUS COMPLETO

### Implementadas (150/175 = 85.7%)
Ver [BUSINESS_RULES_COMPLETE.md](architecture/BUSINESS_RULES_COMPLETE.md) para detalle.

### Pendientes por Módulo

| Módulo | Implementadas | Pendientes | Coverage |
|--------|---------------|------------|----------|
| Product | 7 | 3 (PR-M001, M002, **M003**) | 70% |
| Inventory | 10 | 3 (IV-M001, **M002**, **M003**) | 77% |
| Contacts | 8 | 3 (CO-M001, M002, M003) | 73% |
| Sales | 8 | 3 (**SA-M001**, **SA-M002**, M003) | 73% |
| Purchase | 5 | 4 (**PU-M001**, M002, M003, M004) | 56% |
| Finance | 10 | 3 (**FI-M001**, M002, **FI-M003**) | 77% |
| Accounting | 11 | 3 (**AC-M001**, M002, M003) | 79% |
| Cross-Module | 5 | 2 (CM-M001, M002) | 71% |

**Negrita** = Incluido en este roadmap

---

## 📝 CHECKLIST PARA RELEASE v1.0

### Pre-Release
- [ ] Fase A: Billing configurado y timbrado funcionando
- [ ] Fase B: 3 reglas críticas implementadas
- [ ] Fase C: Event listeners funcionando
- [ ] Tests: 3,100+ pasando (0 failures)
- [ ] Documentación: FRONTEND_GUIDE actualizado

### Release
- [ ] Tag version v1.0.0
- [ ] Migration de staging a production
- [ ] Verificar scheduled commands activos
- [ ] Configurar backups automáticos
- [ ] Monitoreo de errores activo

### Post-Release
- [ ] Fase D: High priority rules
- [ ] Fase E: Medium priority rules
- [ ] Feedback de usuarios
- [ ] Planning v1.1

---

## 📞 PRÓXIMOS PASOS INMEDIATOS

1. **TÚ:** Compartir credenciales SW Sapien (token o user/pass)
2. **TÚ:** Subir .cer y .key a ruta segura
3. **YO:** Configurar `.env` y probar timbrado
4. **YO:** Implementar Fase B (Credit Hold, Reorder Alerts, Three-Way Match)
5. **YO:** Implementar Fase C (Event Listeners)

**¿Listo para comenzar?**

---

**Historial de Fases Completadas:** `docs/archived/roadmap-history/`
