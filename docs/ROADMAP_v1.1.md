# Roadmap v1.1 - Estabilización y Mejoras

**Creado:** 2026-01-03
**Estado Actual:** v1.0-rc1 (99% production ready)

---

## Resumen Ejecutivo

### Lo que TENEMOS (v1.0)
- **18 módulos** completamente funcionales
- **65+ entidades** con CRUD completo
- **697 rutas API** registradas
- **3,300+ tests** (62,000+ assertions)
- **165/175 reglas de negocio** implementadas (94%)
- **Integraciones:** SW Sapien PAC, Stripe, Spatie Audit

### Lo que FALTA para v1.1

| Categoría | Items | Prioridad | Esfuerzo |
|-----------|-------|-----------|----------|
| TODOs en código | 10 reales | Alta | 4-8h |
| Reglas de negocio pendientes | 10 restantes | Media | 20-30h |
| Documentación API | Scribe generation | Media | 2h |
| Refactoring menor | ContactDocument JSON:API | Baja | 3h |

---

## FASE 1: Cleanup de Código (Prioridad Alta)

### 1.1 TODOs Críticos a Resolver

| Archivo | TODO | Acción | Esfuerzo |
|---------|------|--------|----------|
| `ARInvoiceService.php:171` | Calculate tax if needed | Implementar cálculo de IVA | 1h |
| `PaymentService.php:35` | Register Stripe gateway | Ya existe StripeService, solo conectar | 30min |
| `PaymentService.php:274` | Implement webhook processing | Conectar con StripeService.handleWebhook | 1h |
| `CustomerOrderController.php:245` | Generate PDF invoice | Usar CFDIPDFGenerator existente | 1h |
| `PostInventoryMovementToGL.php:327` | Send email notification | Implementar notificación | 30min |

### 1.2 TODOs que Pueden Ignorarse

| Archivo | TODO | Razón |
|---------|------|-------|
| `AuditAuthorizer.php:131-145` | attach/detach relationships | Audit es read-only, no aplica |
| `CFDIPDFGenerator.php:109,128` | XXXXXXXX placeholder | Es para QR code real del SAT |
| `ContactDocumentUploadController.php` | Refactorizar | Funciona, refactor es opcional |
| `Phase3ComprehensiveTest.php:142` | bank_transactions | Ya existe, test desactualizado |

---

## FASE 2: Reglas de Negocio Pendientes (Prioridad Media)

### Ya Implementadas (confirmado en código)
- [x] PR-M003 Product Variants
- [x] IV-M002 Stock Reorder Alerts
- [x] IV-M003 Lot Traceability
- [x] SA-M001 Partial Shipments
- [x] SA-M002 Backorder Management
- [x] PU-M001 Three-Way Match
- [x] FI-M001 Late Payment Penalties
- [x] FI-M003 Credit Hold Automation
- [x] AC-M001 Period Close Checklist

### Pendientes para v1.1

| ID | Regla | Módulo | Esfuerzo | Valor |
|----|-------|--------|----------|-------|
| **IV-M001** | Cycle Count Scheduling | Inventory | 5h | Mejora precisión |
| **CO-M001** | Duplicate Detection | Contacts | 4h | Calidad de datos |
| **SA-M003** | Automatic Discount Rules | Sales | 4h | Automatización |
| **PU-M003** | Budget Control | Purchase | 8h | Control financiero |
| **AC-M002** | Budget vs Actual Tracking | Accounting | 8h | Reportes gerenciales |
| **FI-M002** | Payment Discounts (pronto pago) | Finance | 3h | Incentivo a clientes |

### Pendientes para v1.2+ (Baja prioridad)

| ID | Regla | Módulo | Esfuerzo |
|----|-------|--------|----------|
| PR-M001 | Price History Tracking | Product | 3h |
| PR-M002 | Bulk Price Updates | Product | 2h |
| CO-M002 | Contact Segmentation | Contacts | 3h |
| CO-M003 | Communication Preferences | Contacts | 1h |
| PU-M002 | Supplier Performance Tracking | Purchase | 5h |
| PU-M004 | Blanket PO Support | Purchase | 10h |
| AC-M003 | Multi-Currency Accounting | Accounting | 12h |
| CM-M001 | Sales Forecasting | Cross-Module | 10h |
| CM-M002 | Customer Lifetime Value | Cross-Module | 5h |

---

## FASE 3: Documentación y Calidad

### 3.1 Documentación API
- [ ] Generar documentación con Scribe/L5-Swagger
- [ ] Exportar Postman collection actualizada
- [ ] Actualizar ejemplos en FRONTEND_INTEGRATION_GUIDE

### 3.2 Tests Adicionales
- [ ] Aumentar coverage de Audit (actualmente 50% de modelos)
- [ ] Tests de integración E2E para flujos críticos
- [ ] Tests de performance con datos masivos

### 3.3 Refactoring Opcional
- [ ] ContactDocument: migrar upload a JSON:API puro
- [ ] Unificar servicios de notificación
- [ ] Extraer lógica de cálculo de impuestos a TaxService

---

## FASE 4: Features Nuevos (v1.2+)

### Módulos Potenciales
- **Notifications**: Sistema centralizado de notificaciones (email, SMS, push)
- **Documents**: Gestión documental con versionado
- **Workflows**: Motor de workflows configurables
- **Reporting**: Generador de reportes personalizado

### Integraciones
- [ ] Facturama (alternativa a SW Sapien)
- [ ] Conekta/OpenPay (alternativas a Stripe)
- [ ] WhatsApp Business API
- [ ] Google Analytics / Mixpanel

---

## Métricas de Éxito

### v1.0 Release Criteria
- [x] 0 tests fallando
- [x] 0 errores críticos en código
- [x] Todas las integraciones funcionando (PAC, Stripe)
- [x] Documentación de frontend actualizada
- [ ] Documentación API generada (Scribe)

### v1.1 Release Criteria
- [ ] TODOs críticos resueltos (5 items)
- [ ] 6 reglas de negocio adicionales
- [ ] Coverage de Audit al 70%
- [ ] Tests E2E para Order-to-Cash y Procure-to-Pay

---

## Timeline Sugerido

```
v1.0 RELEASE (Actual)
├── Status: 99% ready
├── Blocker: Solo falta documentación API
└── Acción: Generar docs con Scribe, tag v1.0.0

v1.1 (2-3 semanas)
├── Semana 1: TODOs críticos + IV-M001, CO-M001
├── Semana 2: SA-M003, FI-M002, documentación
└── Semana 3: Testing, refinamiento, release

v1.2 (4-6 semanas después de v1.1)
├── PU-M003 Budget Control
├── AC-M002 Budget vs Actual
├── Módulo Notifications
└── Integraciones adicionales
```

---

## Notas Técnicas

### Deuda Técnica Conocida
1. **ContactDocument upload** usa controller tradicional en lugar de JSON:API
2. **Tax calculation** hardcoded en algunos lugares (debería ser configurable)
3. **Audit coverage** solo cubre 50% de modelos (37/74)

### Mejoras de Performance Pendientes
1. Implementar queue para operaciones pesadas (PDF generation, emails)
2. Cache de consultas frecuentes (productos, precios)
3. Lazy loading optimizado en relaciones complejas

### Seguridad
- [x] Rate limiting implementado
- [x] Headers de seguridad configurados
- [ ] Revisar permisos granulares en endpoints sensibles
- [ ] Implementar audit log para accesos fallidos
