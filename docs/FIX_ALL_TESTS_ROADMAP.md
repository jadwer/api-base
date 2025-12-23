# Roadmap: Arreglar Todos los Tests

**Fecha:** 2025-12-23
**Estado Inicial:** 644 failed, 2444 passed
**Objetivo:** 0 failed, 3088+ passed

---

## Metodologia de Trabajo

### ANTES de modificar cualquier archivo, SIEMPRE revisar:

1. **Modelo** (`app/Models/Entity.php`)
   - Relaciones definidas
   - Fillable fields
   - Casts
   - Scopes
   - Accessors/Mutators

2. **Migracion** (`Database/migrations/`)
   - Estructura de tabla
   - Foreign keys
   - Constraints (unique, nullable)
   - Indices

3. **Schema** (`app/JsonApi/V1/Entity/EntitySchema.php`)
   - Fields definidos
   - Relaciones JSON:API
   - Filters habilitados
   - Sorting habilitado

4. **Request** (`app/JsonApi/V1/Entity/EntityRequest.php`)
   - Validation rules
   - Required vs optional fields
   - Store vs Update rules

5. **Authorizer** (`app/JsonApi/V1/Entity/EntityAuthorizer.php`)
   - Permisos requeridos
   - Logica de autorizacion

6. **Factory** (`Database/factories/EntityFactory.php`)
   - Datos generados
   - Relaciones creadas
   - Estados (states)

7. **Tests** (`Tests/Feature/Entity*.php`)
   - Que espera el test
   - Datos de prueba usados

---

## Analisis de Errores por Modulo

### Resumen de Fallos

| Modulo | Fallos | Tipo Principal de Error |
|--------|--------|------------------------|
| Reports | 190 | UniqueConstraintViolationException |
| HR | ~66 | Store/Update validation |
| Finance | ~51 | QueryException |
| Billing | ~50 | BadMethodCallException, QueryException |
| Ecommerce | ~40 | Various |
| Sales | ~25 | CRUD failures |
| Inventory | ~11 | ValidationException |
| **Total** | **644** | |

---

## Causa Raiz Identificada

### FiscalPeriod Factory - Foreign Key Constraint

```
SQLSTATE[23000]: Integrity constraint violation: 1452
Cannot add or update a child row: a foreign key constraint fails
(`api-base-test`.`fiscal_periods`, CONSTRAINT `fiscal_periods_closed_by_id_foreign`
FOREIGN KEY (`closed_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT)
```

**Problema:** El factory de `FiscalPeriod` usa `closed_by_id = 1` sin asegurar que el usuario existe.

**Impacto:** Cualquier test que cree datos de Accounting/Finance falla en cascada.

---

## Plan de Ejecucion

### Fase 1: Arreglar Causa Raiz (Alta Prioridad)

#### 1.1 FiscalPeriod Factory
- [ ] Revisar `Modules/Accounting/Database/factories/FiscalPeriodFactory.php`
- [ ] Revisar `Modules/Accounting/app/Models/FiscalPeriod.php`
- [ ] Revisar `Modules/Accounting/Database/migrations/*fiscal_periods*`
- [ ] Corregir factory para crear usuario relacionado si es necesario
- [ ] Ejecutar tests de Accounting para verificar

**Archivos a revisar:**
```
Modules/Accounting/
├── app/Models/FiscalPeriod.php
├── Database/factories/FiscalPeriodFactory.php
├── Database/migrations/*fiscal_periods*
└── Tests/Feature/FiscalPeriod*.php
```

---

### Fase 2: Reports Module (190 fallos)

#### 2.1 Contexto
- [ ] Revisar que seeders/factories usan los tests de Reports
- [ ] Identificar dependencia con FiscalPeriod

#### 2.2 Archivos a revisar
```
Modules/Reports/
├── Tests/Feature/*.php
├── Database/factories/
└── Database/seeders/
```

---

### Fase 3: HR Module (~66 fallos)

#### 3.1 Tests afectados
- PositionStoreTest (11)
- PayrollItemStoreTest (11)
- EmployeeStoreTest (11)
- LeaveStoreTest (9)
- DepartmentStoreTest (9)
- AttendanceStoreTest (9)
- EmployeeUpdateTest (7)
- DepartmentUpdateTest (7)

#### 3.2 Por cada entidad, revisar:
- [ ] Modelo
- [ ] Migracion
- [ ] Schema
- [ ] Request
- [ ] Authorizer
- [ ] Factory
- [ ] Test

---

### Fase 4: Finance Module (~51 fallos)

#### 4.1 Tests afectados
- CreditManagementServiceTest (10) - Unit
- AgingAnalysisServiceTest (9) - Integration
- PaymentApplicationIntegrationTest (8)
- EdgeCaseIntegrationTest (8)
- Phase3ComprehensiveTest (7)
- BankReconciliationServiceTest (7) - Unit
- ARInvoiceGLPostingTest (6)

#### 4.2 Revisar servicios
```
Modules/Finance/
├── app/Services/
│   ├── CreditManagementService.php
│   ├── AgingAnalysisService.php
│   └── BankReconciliationService.php
└── Tests/
    ├── Unit/
    └── Integration/
```

---

### Fase 5: Billing Module (~50 fallos)

#### 5.1 Tests afectados
- CompanySettingStoreTest (14)
- PaymentTransactionTest (12) - BadMethodCallException
- CompanySettingUpdateTest (8)
- CFDIStampingTest (8)
- CFDIItemUpdateTest (8)
- CFDIInvoiceIndexTest (8)

#### 5.2 Revisar
- [ ] PaymentTransaction - BadMethodCallException sugiere metodo faltante
- [ ] CompanySetting - validation issues
- [ ] CFDI tests - integration issues

---

### Fase 6: Ecommerce Module (~40 fallos)

#### 6.1 Tests afectados
- ProductReviewStoreTest (9)
- ProductComparisonStoreTest (9)
- WishlistItemUpdateTest (8)
- ProductReviewDestroyTest (8)
- WishlistItemStoreTest (7)
- ProductReviewUpdateTest (7)
- ProductRecommendationTest (3)
- RecommendationEngineTest (4)

---

### Fase 7: Sales Module (~25 fallos)

#### 7.1 Tests afectados
- SalesOrderStoreTest (9)
- SalesOrderItemStoreTest (7)
- SalesOrderUpdateTest (3)
- SalesOrderShowTest (3+)
- SalesOrderItemUpdateTest (2+)

---

### Fase 8: Inventory Module (~11 fallos)

#### 8.1 Tests afectados
- InventoryMovementTest (7) - ValidationException
- InventoryMovementGLIntegrationTest (4)

---

## Orden de Ejecucion Recomendado

1. **Fase 1** - FiscalPeriod Factory (causa raiz)
   - Esto deberia resolver ~200+ fallos automaticamente

2. **Fase 3** - HR Module
   - Modulo independiente, facil de aislar

3. **Fase 5** - Billing Module
   - BadMethodCallException es error claro

4. **Fase 7** - Sales Module
   - Core business logic

5. **Fase 8** - Inventory Module
   - Pocos fallos, impacto alto

6. **Fase 6** - Ecommerce Module
   - Depende de otros modulos

7. **Fase 4** - Finance Module
   - Tests de integracion complejos

8. **Fase 2** - Reports Module
   - Deberia resolverse con Fase 1

---

## Verificacion

Despues de cada fase:
```bash
# Ejecutar tests del modulo especifico
php artisan test Modules/{ModuleName}/Tests/

# Verificar que no rompimos otros tests
php artisan test --stop-on-failure
```

---

## Notas Importantes

1. **NO hacer cambios sin entender el contexto**
2. **Siempre revisar los 7 archivos antes de modificar**
3. **Ejecutar tests despues de cada cambio**
4. **Documentar cambios realizados**
5. **Si hay duda, preguntar antes de actuar**

---

## Progreso

| Fase | Estado | Fallos Antes | Fallos Despues | Notas |
|------|--------|--------------|----------------|-------|
| 1 | ✅ COMPLETO | 644 | TBD | FiscalPeriodFactory corregido |
| 2 | Pendiente | 190 | TBD | Esperando resultados de Fase 1 |
| 3 | Pendiente | ~66 | - | - |
| 4 | Pendiente | ~51 | - | - |
| 5 | ✅ COMPLETO | ~50 | TBD | assertJsonApiIncluded + camelCase |
| 6 | Pendiente | ~40 | - | - |
| 7 | Pendiente | ~25 | - | - |
| 8 | Pendiente | ~11 | - | - |

---

## Fase 5: Cambios Realizados (Billing)

### Archivos corregidos:

1. `Modules/Billing/tests/Feature/PaymentTransactionIndexTest.php`
   - Corregido `assertJsonApiIncluded` → uso de `assertJsonStructure` + `json('included')`

2. `Modules/Billing/tests/Feature/PaymentTransactionShowTest.php`
   - Corregido `assertJsonApiIncluded` → uso de `assertJsonStructure` + `json('included')`

3. `Modules/Billing/tests/Feature/CompanySettingStoreTest.php`
   - Corregido `company_name` → `companyName`
   - Corregido `is_active` → `isActive`

4. `Modules/Billing/tests/Feature/CompanySettingUpdateTest.php`
   - Corregido `company_name` → `companyName`
   - Corregido `is_active` → `isActive`

**Contexto revisado:**
- ✅ Schema: CompanySettingSchema.php (usa camelCase correctamente)
- ✅ Model: CompanySetting.php
- ✅ Tests: Corregidos para usar camelCase en JSON:API attributes

---

## Fase 1: Cambios Realizados

### Archivo: `Modules/Accounting/Database/Factories/FiscalPeriodFactory.php`

**Cambios:**
1. `definition()`: Cambiado `status` de aleatorio a siempre `'open'`
2. `closed()`: Ahora usa `User::first() ?? User::factory()->create()` en lugar de `closed_by_id = 1`
3. Agregado nuevo state `locked()` con la misma logica

**Contexto revisado antes del cambio:**
- ✅ Modelo: FiscalPeriod.php
- ✅ Migracion: 2025_10_24_101720_create_fiscal_periods_table.php
- ✅ Schema: FiscalPeriodSchema.php
- ✅ Request: FiscalPeriodRequest.php
- ✅ Factory: FiscalPeriodFactory.php

**Verificacion:**
```bash
# Tinker test exitoso
Default period created: 2020-01-xxx - Status: open
Closed period created: 2020-02-xxx - Status: closed - Closed by: 1
SUCCESS: Factory works correctly!
```

---

**Ultima Actualizacion:** 2025-12-23 03:30
