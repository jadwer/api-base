# API DOCUMENTATION - Backend ↔ Frontend Communication

**Propósito:** Documentación compartida entre equipos Backend y Frontend
**Última actualización:** 2025-10-25

---

## 📁 ESTRUCTURA DE CARPETAS

```
docs/api-documentation/
├── README.md (este archivo)
├── backend-specs/          # Backend escribe aquí
│   ├── COMPLETE_API_REFERENCE.md
│   ├── modules/
│   │   ├── products.md
│   │   ├── inventory.md
│   │   ├── sales.md
│   │   ├── purchase.md
│   │   ├── finance.md
│   │   ├── accounting.md
│   │   ├── contacts.md
│   │   └── ecommerce.md
│   └── CHANGELOG_API.md
│
├── frontend-requirements/  # Frontend escribe aquí
│   ├── NEEDED_ENDPOINTS.md
│   ├── ISSUES_FOUND.md
│   └── FEATURE_REQUESTS.md
│
└── shared-contracts/       # Ambos equipos
    ├── SYNC_STATUS.md
    ├── BREAKING_CHANGES.md
    └── MIGRATION_PLANS.md
```

---

## 🎯 CÓMO USAR ESTA DOCUMENTACIÓN

### Para Backend Team:
1. **Escribe specs en `backend-specs/`** cuando crees o modifiques endpoints
2. **Documenta breaking changes en `shared-contracts/BREAKING_CHANGES.md`**
3. **Actualiza `CHANGELOG_API.md`** con cada cambio
4. **Lee `frontend-requirements/`** para saber qué necesita Frontend

### Para Frontend Team:
1. **Lee `backend-specs/`** para saber qué endpoints están disponibles
2. **Escribe en `frontend-requirements/`** qué endpoints necesitas
3. **Reporta issues en `frontend-requirements/ISSUES_FOUND.md`**
4. **Consulta `shared-contracts/SYNC_STATUS.md`** para ver estado de sincronización

---

## 📊 RECURSOS DISPONIBLES

### Módulos Completos (100% Backend)

| Módulo | Resources | Documentación | Frontend Status |
|--------|-----------|---------------|-----------------|
| **Products** | 4 | ✅ [Ver](backend-specs/modules/products.md) | ✅ Implementado |
| **Inventory** | 6 | ✅ [Ver](backend-specs/modules/inventory.md) | ✅ Implementado |
| **Sales** | 3 | ✅ [Ver](backend-specs/modules/sales.md) | ✅ Implementado |
| **Purchase** | 3 | ✅ [Ver](backend-specs/modules/purchase.md) | ✅ Implementado |
| **Contacts** | 5 | ✅ [Ver](backend-specs/modules/contacts.md) | ✅ Implementado |
| **Ecommerce** | 3 | ✅ [Ver](backend-specs/modules/ecommerce.md) | ✅ Implementado |
| **Finance** | 7 | ✅ [Ver](backend-specs/modules/finance.md) | ⚠️ Parcial (71%) |
| **Accounting** | 12 | ✅ [Ver](backend-specs/modules/accounting.md) | ⚠️ Parcial (33%) |

**Total:** 43 resources across 8 modules

---

## 🚨 ISSUES ACTUALES (URGENTES)

### 1. Finance Module - URL Mismatch
**Backend usa:** `ap-invoices`, `ar-invoices`, `payments`
**Frontend usa:** `a-p-invoices`, `a-r-invoices`, `a-p-payments`, `a-r-receipts`
**Status:** ⚠️ BREAKING - Frontend needs update
**Prioridad:** 🔴 CRÍTICA
**Detalles:** [shared-contracts/SYNC_STATUS.md](shared-contracts/SYNC_STATUS.md)

### 2. Payment Architecture Change
**Backend:** Unificó `APPayment` + `ARReceipt` en `Payment`
**Frontend:** Aún usa entities separadas
**Status:** ⚠️ BREAKING - Major refactor needed
**Prioridad:** 🔴 ALTA

### 3. Missing Entities in Frontend
- `payment-applications` (NUEVO en backend)
- `payment-methods` (sin UI en frontend)
- 8 Accounting entities sin implementar

---

## 📖 GUÍAS RÁPIDAS

### Quick Start para Frontend
```bash
# 1. Lee la referencia completa
cat docs/api-documentation/backend-specs/COMPLETE_API_REFERENCE.md

# 2. Revisa tu módulo específico
cat docs/api-documentation/backend-specs/modules/finance.md

# 3. Verifica estado de sincronización
cat docs/api-documentation/shared-contracts/SYNC_STATUS.md
```

### Quick Start para Backend
```bash
# 1. Actualiza specs cuando hagas cambios
vim docs/api-documentation/backend-specs/modules/finance.md

# 2. Documenta breaking changes
vim docs/api-documentation/shared-contracts/BREAKING_CHANGES.md

# 3. Lee qué necesita Frontend
cat docs/api-documentation/frontend-requirements/NEEDED_ENDPOINTS.md
```

---

## 🔗 ENLACES ÚTILES

- **API Base URL:** `http://localhost:8000/api/v1`
- **JSON:API Version:** 1.1
- **Auth:** Laravel Sanctum (Bearer token)
- **Postman Collection:** (pendiente)

---

## 📝 CONVENCIONES

### Naming Conventions
- **URLs:** kebab-case (`ar-invoices`, `bank-accounts`)
- **Resource Types:** kebab-case (`"ar-invoices"`, `"bank-accounts"`)
- **Attributes:** camelCase (`invoiceNumber`, `totalAmount`)
- **Database:** snake_case (`invoice_number`, `total_amount`)

### Response Format (JSON:API)
```json
{
  "jsonapi": { "version": "1.0" },
  "data": {
    "type": "ar-invoices",
    "id": "1",
    "attributes": {
      "invoiceNumber": "AR-001",
      "totalAmount": 1500.00
    },
    "relationships": {
      "customer": {
        "data": { "type": "contacts", "id": "5" }
      }
    }
  },
  "included": [...]
}
```

---

## 🤝 CONTRIBUYENDO

### Backend añade un endpoint:
1. Implementar endpoint
2. Escribir tests (100% coverage required)
3. Documentar en `backend-specs/modules/{module}.md`
4. Actualizar `CHANGELOG_API.md`
5. Si es breaking change, documentar en `BREAKING_CHANGES.md`

### Frontend necesita algo:
1. Escribir en `frontend-requirements/NEEDED_ENDPOINTS.md`
2. Describir use case y estructura esperada
3. Backend prioriza y implementa
4. Frontend recibe notificación cuando esté listo

### Reportar issue:
1. Usar `frontend-requirements/ISSUES_FOUND.md`
2. Incluir: endpoint, payload enviado, respuesta recibida, error
3. Backend investiga y responde
4. Actualizar documentación cuando se fixe

---

**Contacto:**
- Backend Team: Revisar PRs en `api-base`
- Frontend Team: Revisar PRs en `webapp-base`
- Issues compartidos: Esta carpeta de documentación

**Última revisión:** 2025-10-25
