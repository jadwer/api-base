# FRONTEND-BACKEND SYNCHRONIZATION REPORT

**Fecha:** 2025-10-25
**Status:** DESAJUSTE CRÍTICO IDENTIFICADO
**Acción requerida:** Actualizar URLs y estructura de datos en Frontend

---

## PROBLEMA CRÍTICO IDENTIFICADO

### URLs DESAJUSTADAS

**Backend actual (CORRECTO):**
```
✅ /api/v1/ar-invoices         (Facturas por Cobrar)
✅ /api/v1/ap-invoices         (Facturas por Pagar)
✅ /api/v1/payments            (Pagos - reemplaza a-p-payments y a-r-receipts)
✅ /api/v1/payment-applications (Aplicación de pagos a facturas)
✅ /api/v1/bank-accounts
✅ /api/v1/payment-methods
```

**Frontend actual (PARCIALMENTE INCORRECTO):**
```
✅ /api/v1/a-p-invoices        → Debe cambiar a /api/v1/ap-invoices
✅ /api/v1/a-r-invoices        → Debe cambiar a /api/v1/ar-invoices
❌ /api/v1/a-p-payments        → NO EXISTE - cambió a /api/v1/payments
❌ /api/v1/a-r-receipts        → NO EXISTE - cambió a /api/v1/payments
```

### CAMBIO ARQUITECTÓNICO IMPORTANTE

**ANTES (Viejo - Lo que Frontend aún espera):**
- `a-p-payments` (Pagos a Proveedores)
- `a-r-receipts` (Recibos de Clientes)
- **Problema:** 2 entidades separadas para básicamente lo mismo

**AHORA (Nuevo - Lo que Backend implementa):**
- `payments` (UN solo endpoint para TODOS los pagos)
- `payment-applications` (Aplicación de pagos a facturas específicas)
- **Ventaja:** Arquitectura más limpia, menos redundancia

---

## MAPEO DE ENTIDADES BACKEND → FRONTEND

### 1. AR Invoice (Facturas por Cobrar)

**Backend URL:** `/api/v1/ar-invoices`

**Estructura JSON:API:**
```typescript
interface ARInvoice {
  id: string;
  type: "ar-invoices";  // ⚠️ Frontend usa "a-r-invoices"
  attributes: {
    invoiceNumber: string;
    invoiceDate: string;        // ISO date
    dueDate: string;
    customerId: number;         // ⚠️ Frontend espera "contactId"
    currency: string;
    subtotal: number;
    taxAmount: number;
    totalAmount: number;
    paidAmount: number;         // Calculado
    status: string;             // draft, posted, paid, cancelled
    journalEntryId: number | null;
    notes: string | null;
    metadata: object;
    isActive: boolean;
    createdAt: string;
    updatedAt: string;
  };
  relationships: {
    customer?: RelationshipObject;        // BelongsTo Contact (is_customer=true)
    journalEntry?: RelationshipObject;    // BelongsTo JournalEntry
    paymentApplications?: RelationshipObject; // HasMany PaymentApplication
  }
}
```

**Includes disponibles:**
```
?include=customer,journalEntry,paymentApplications
```

### 2. AP Invoice (Facturas por Pagar)

**Backend URL:** `/api/v1/ap-invoices`

**Estructura JSON:API:**
```typescript
interface APInvoice {
  id: string;
  type: "ap-invoices";  // ⚠️ Frontend usa "a-p-invoices"
  attributes: {
    invoiceNumber: string;
    invoiceDate: string;
    dueDate: string;
    supplierId: number;         // ⚠️ Frontend espera "contactId"
    currency: string;
    subtotal: number;
    taxAmount: number;
    totalAmount: number;
    paidAmount: number;         // Calculado
    status: string;
    journalEntryId: number | null;
    notes: string | null;
    metadata: object;
    isActive: boolean;
    createdAt: string;
    updatedAt: string;
  };
  relationships: {
    supplier?: RelationshipObject;        // BelongsTo Contact (is_supplier=true)
    journalEntry?: RelationshipObject;
  }
}
```

**Includes disponibles:**
```
?include=supplier,journalEntry
```

### 3. Payment (NUEVO - Reemplaza a-p-payments y a-r-receipts)

**Backend URL:** `/api/v1/payments`

**Estructura JSON:API:**
```typescript
interface Payment {
  id: string;
  type: "payments";
  attributes: {
    paymentNumber: string;
    paymentDate: string;
    customerId: number;         // Para payments AR
    bankAccountId: number;
    paymentMethodId: number;
    amount: number;
    currency: string;
    appliedAmount: number;      // Cuánto se ha aplicado a invoices
    unappliedAmount: number;    // Cuánto queda sin aplicar
    status: string;             // unapplied, partial, applied
    journalEntryId: number | null;
    reference: string | null;
    notes: string | null;
    metadata: object;
    isActive: boolean;
    createdAt: string;
    updatedAt: string;
  };
  relationships: {
    customer?: RelationshipObject;
    bankAccount?: RelationshipObject;
    paymentMethod?: RelationshipObject;
    journalEntry?: RelationshipObject;
    paymentApplications?: RelationshipObject; // HasMany
  }
}
```

**Includes disponibles:**
```
?include=customer,bankAccount,paymentMethod,journalEntry,paymentApplications
```

### 4. Payment Application (NUEVO)

**Backend URL:** `/api/v1/payment-applications`

**Estructura JSON:API:**
```typescript
interface PaymentApplication {
  id: string;
  type: "payment-applications";
  attributes: {
    paymentId: number;
    arInvoiceId: number;
    amount: number;
    applicationDate: string;
    notes: string | null;
    isActive: boolean;
  };
  relationships: {
    payment?: RelationshipObject;
    arInvoice?: RelationshipObject;
  }
}
```

---

## CAMBIOS REQUERIDOS EN FRONTEND

### PRIORIDAD 1: Actualizar URLs (Breaking Change)

**Archivo:** `src/modules/finance/services/index.ts`

```typescript
// ❌ CAMBIAR ESTO:
const AP_INVOICES_URL = '/api/v1/a-p-invoices';
const AR_INVOICES_URL = '/api/v1/a-r-invoices';
const AP_PAYMENTS_URL = '/api/v1/a-p-payments';
const AR_RECEIPTS_URL = '/api/v1/a-r-receipts';

// ✅ POR ESTO:
const AP_INVOICES_URL = '/api/v1/ap-invoices';
const AR_INVOICES_URL = '/api/v1/ar-invoices';
const PAYMENTS_URL = '/api/v1/payments';           // UNIFICADO
const PAYMENT_APPLICATIONS_URL = '/api/v1/payment-applications'; // NUEVO
```

### PRIORIDAD 2: Actualizar Tipos de Recursos

**Archivo:** `src/modules/finance/utils/transformers.ts`

```typescript
// ❌ CAMBIAR:
type: "a-p-invoices"
type: "a-r-invoices"
type: "a-p-payments"
type: "a-r-receipts"

// ✅ POR:
type: "ap-invoices"
type: "ar-invoices"
type: "payments"
type: "payment-applications"
```

### PRIORIDAD 3: Actualizar Campos de Relación

**AR/AP Invoices:**
```typescript
// ❌ ANTES (Frontend):
contactId: number;

// ✅ AHORA (Backend real):
customerId: number;  // para AR Invoice
supplierId: number;  // para AP Invoice

// Relación:
customer: Contact (con is_customer=true)
supplier: Contact (con is_supplier=true)
```

### PRIORIDAD 4: Nuevos Campos Calculados

**AR/AP Invoices tienen campos calculados:**
```typescript
paidAmount: number;          // Calculado por backend
remainingBalance: number;    // = totalAmount - paidAmount (calculado)
```

**Payments tienen:**
```typescript
appliedAmount: number;       // Cuánto se aplicó a invoices
unappliedAmount: number;     // Cuánto queda sin aplicar
status: 'unapplied' | 'partial' | 'applied';
```

---

## MIGRATION STRATEGY (Recomendada)

### FASE 1: Deprecation Warnings (Semana 1)
1. Agregar console.warn cuando se usen URLs viejas
2. Crear wrappers de compatibilidad temporal
3. Documentar cambios en CHANGELOG

### FASE 2: Dual Support (Semana 2)
1. Soportar ambas URLs temporalmente
2. Migrar componentes gradualmente
3. Tests para ambas versiones

### FASE 3: Migration Complete (Semana 3)
1. Remover URLs viejas
2. Actualizar toda la UI
3. Eliminar código legacy

### FASE 4: New Features (Semana 4+)
1. Implementar PaymentApplications UI
2. Pantalla de aplicación de pagos a facturas
3. Dashboard con saldos pendientes

---

## FIELDS MAPPING REFERENCE

### Backend → Frontend Field Name Changes

| Backend Field | Frontend Field (esperado) | Tipo | Notas |
|--------------|--------------------------|------|-------|
| `customerId` | `contactId` | number | AR Invoice |
| `supplierId` | `contactId` | number | AP Invoice |
| `invoiceNumber` | `invoiceNumber` | string | ✅ OK |
| `invoiceDate` | `invoiceDate` | string | ✅ OK |
| `dueDate` | `dueDate` | string | ✅ OK |
| `totalAmount` | `totalAmount` | number | ✅ OK |
| `paidAmount` | `paidAmount` | number | ✅ Calculado |
| `journalEntryId` | `journalEntryId` | number\|null | ✅ OK |

---

## INCLUDES STRATEGY

### Recomendaciones para Frontend

**Para listar invoices con datos completos:**
```
GET /api/v1/ar-invoices?include=customer,journalEntry,paymentApplications
GET /api/v1/ap-invoices?include=supplier,journalEntry
```

**Para payments con contexto:**
```
GET /api/v1/payments?include=customer,bankAccount,paymentMethod,paymentApplications
```

**Para detail views:**
```
GET /api/v1/ar-invoices/123?include=customer,journalEntry,paymentApplications.payment
```

---

## TESTING CHECKLIST

### Backend API (✅ Completado)
- [x] `/api/v1/ar-invoices` CRUD
- [x] `/api/v1/ap-invoices` CRUD
- [x] `/api/v1/payments` CRUD
- [x] `/api/v1/payment-applications` CRUD
- [x] GL Posting automático funcional
- [x] Campos calculados (paidAmount, remainingBalance)

### Frontend (❌ Pendiente)
- [ ] Actualizar URLs a nueva nomenclatura
- [ ] Actualizar tipos de recursos
- [ ] Corregir field mappings (customerId vs contactId)
- [ ] Agregar soporte para PaymentApplications
- [ ] Tests de integración Front-Back
- [ ] Actualizar documentación

---

## PRÓXIMOS PASOS RECOMENDADOS

1. **INMEDIATO:** Crear branch `feature/finance-api-migration` en Frontend
2. **DÍA 1:** Actualizar URLs y tipos en services
3. **DÍA 2:** Actualizar transformers y interfaces
4. **DÍA 3:** Migrar componentes y pages
5. **DÍA 4:** Testing y bugfixes
6. **DÍA 5:** Merge y deploy

---

## COMANDOS ÚTILES PARA VERIFICACIÓN

### Backend (API)
```bash
# Listar rutas Finance
php artisan route:list --path=api/v1 | grep -E "invoices|payments"

# Test AR Invoice
curl http://localhost:8000/api/v1/ar-invoices?include=customer

# Test Payments
curl http://localhost:8000/api/v1/payments?include=customer,bankAccount
```

### Frontend (Next.js)
```bash
# Buscar referencias a URLs viejas
grep -r "a-p-payments\|a-r-receipts" src/

# Buscar tipos de recursos viejos
grep -r '"a-p-invoices"\|"a-r-invoices"' src/

# Ejecutar tests
npm run test
```

---

**Última actualización:** 2025-10-25
**Próxima revisión:** Después de alcanzar 100% tests en Backend
