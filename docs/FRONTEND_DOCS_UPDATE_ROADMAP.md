# Roadmap de Actualización de Documentación Frontend

**Fecha de inicio:** 2025-12-24
**Estado:** En progreso

## Resumen Ejecutivo

Se identificaron discrepancias significativas entre la documentación frontend y los schemas/modelos actuales en 12 módulos. Este roadmap organiza el trabajo por prioridad.

---

## Fase 1: CRÍTICO (Bloquea desarrollo frontend)

### 1.1 REPORTS - Schemas incorrectos
- [ ] ARAgingReportSchema - Reescribir campos (copiado de BalanceSheet)
- [ ] APAgingReportSchema - Reescribir campos
- [ ] SalesByCustomerReportSchema - Reescribir campos
- [ ] SalesByProductReportSchema - Reescribir campos
- [ ] PurchaseBySupplierReportSchema - Reescribir campos
- [ ] PurchaseByProductReportSchema - Reescribir campos

### 1.2 HR - Campos renombrados
- [ ] PayrollItem: `baseSalary`→`basicSalary`, `overtime`→`overtimePay`, etc.
- [ ] PerformanceReview: `periodStart`→`reviewPeriodStart`, nuevos ratings

### 1.3 INVENTORY - Campos renombrados
- [ ] InventoryMovement: `type`→`movementType`, `reason`→`description`
- [ ] WarehouseLocation: eliminar `bin`, agregar 14 campos nuevos
- [ ] ProductBatch: 15 campos nuevos, estructura diferente

### 1.4 SALES - Campos inexistentes
- [ ] Eliminar `subtotalAmount`, `taxAmount` de documentación
- [ ] Eliminar endpoints OrderTracking no implementados
- [ ] Corregir status enum (6 valores reales vs 9 documentados)

---

## Fase 2: ALTO (Entidades faltantes)

### 2.1 ACCOUNTING - 5 entidades nuevas
- [ ] Journal - Tipos de diarios contables
- [ ] JournalSequence - Control de secuencias
- [ ] AccountMapping - Mapeos de cuentas
- [ ] AccountBalance - Saldos por período
- [ ] IdempotencyKey - Claves de idempotencia
- [ ] ExchangeRatePolicy - Políticas de tasas

### 2.2 BILLING - Entidad faltante
- [ ] PaymentTransaction - Completamente sin documentar

### 2.3 ECOMMERCE - 5 entidades faltantes
- [ ] CartItem - Sección formal
- [ ] ProductQuestion
- [ ] ProductAnswer
- [ ] ProductComparison
- [ ] ProductComparisonItem
- [ ] InventoryReservation

### 2.4 HR - Entidades sin sección
- [ ] Department - Agregar documentación completa
- [ ] Position - Agregar documentación completa
- [ ] LeaveType - Agregar documentación completa

### 2.5 FINANCE - Campos de reconciliación
- [ ] APInvoice: reconciliationStatus, reconciledAt, reconciledBy, etc.
- [ ] PaymentApplication: campos renombrados

---

## Fase 3: MEDIO (Campos/tipos incorrectos)

### 3.1 ACCOUNTING
- [ ] ExchangeRate: cambiar `isActive` a `status`
- [ ] JournalEntry: agregar `approvedAt`, `approvedById`

### 3.2 CONTACTS
- [ ] Contact: 4 campos de crédito nuevos
- [ ] ContactDocument: tipos de documento México-específicos
- [ ] ContactDocument: campo `status` computado

### 3.3 CRM
- [ ] Activity: estados corregir (`scheduled` existe, `in_progress` no)
- [ ] Activity: eliminar `dueDate`, `completedAt`, `priority` (no existen)
- [ ] Campaign: usar `campaignType` en ejemplos

### 3.4 PRODUCT
- [ ] Unit: cambiar `abbreviation` a `code` + `unitType`
- [ ] Category/Brand: agregar `slug`, `productsCount`

### 3.5 PURCHASE
- [ ] PurchaseOrderItem: agregar `receivedQuantity`
- [ ] Include paths anidados

### 3.6 FINANCE
- [ ] BankAccount: agregar `glAccountId`, `openingBalance`, `status`
- [ ] PaymentMethod: agregar `type`, `requiresReference`

---

## Fase 4: BAJO (Filtros/sorting)

- [ ] Documentar filtros completos para todas las entidades
- [ ] Documentar campos sortable faltantes
- [ ] Agregar ejemplos de queries con filtros avanzados

---

## Progreso

| Fase | Total | Completado | % |
|------|-------|------------|---|
| Fase 1 | 4 módulos | 0 | 0% |
| Fase 2 | 5 módulos | 0 | 0% |
| Fase 3 | 6 módulos | 0 | 0% |
| Fase 4 | General | 0 | 0% |

---

## Log de Cambios

### 2025-12-24
- Creado roadmap inicial
- Identificadas discrepancias en 12 módulos
