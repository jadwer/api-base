# Guía Frontend - Finance & Accounting Fase 1

**Documento para Equipo Frontend**  
**Fecha:** 20 de Agosto, 2025  
**Status:** Implementación lista - Endpoints funcionales  
**Backend Modules:** Accounting + Finance  

---

## 🎯 **ALCANCE FASE 1 - LO QUE SÍ IMPLEMENTAR**

**Objetivo**: Funcionalidad contable-financiera básica operativa para manejo de facturas, pagos y contabilización automática.

**Filosofía**: Simple, directo, funcional. Toda la lógica compleja la maneja el backend automáticamente.

---

## 📋 **MÓDULO ACCOUNTING (Contabilidad)**

### **📚 Catálogo de Cuentas**

#### **Endpoint:** `GET /api/v1/accounts`
```json
{
  "data": [
    {
      "type": "accounts",
      "id": "1",
      "attributes": {
        "code": "1100",
        "name": "Banco",
        "accountType": "asset",
        "level": 1,
        "parentId": null,
        "currency": "MXN",
        "isPostable": true,
        "status": "active"
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Listado:** Tabla con code, name, accountType, isPostable
- **Filtros:** `?filter[accountType]=asset&filter[isPostable]=1`
- **Estados:** active/inactive (básico)
- **⚠️ IMPORTANTE:** Solo cuentas con `isPostable: true` aparecen en asientos contables

---

### **📖 Asientos Contables**

#### **Endpoint:** `GET /api/v1/journal-entries`
```json
{
  "data": [
    {
      "type": "journal-entries",
      "id": "1",
      "attributes": {
        "date": "2025-08-20",
        "number": "JE-001",
        "description": "Pago a proveedor",
        "status": "posted",
        "sourceType": "ap_payment",
        "sourceId": 5,
        "totalDebit": 1000.00,
        "totalCredit": 1000.00
      },
      "relationships": {
        "journalLines": {
          "data": [
            {"type": "journal-lines", "id": "1"},
            {"type": "journal-lines", "id": "2"}
          ]
        }
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Listado:** Tabla con date, number, description, status, totals
- **Estados:** `draft` (editable) → `posted` (inmutable, color verde)
- **Detalle:** Mostrar líneas incluidas `?include=journalLines,journalLines.account`
- **Filtros:** `?filter[status]=posted&filter[sourceType]=ap_payment`

---

### **📝 Líneas de Asiento**

#### **Endpoint:** `GET /api/v1/journal-lines?include=account`
```json
{
  "data": [
    {
      "type": "journal-lines",
      "id": "1",
      "attributes": {
        "journalEntryId": 1,
        "accountId": 5,
        "description": "Gasto de oficina",
        "debit": 1000.00,
        "credit": 0.00
      },
      "relationships": {
        "account": {
          "data": {"type": "accounts", "id": "5"}
        }
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Tabla detalle:** account.code, account.name, description, debit, credit
- **Validación:** Σ(debit) = Σ(credit) antes de postear
- **Solo cuentas postables:** Dropdown filtrado por `isPostable: true`

---

### **🔄 Posteo de Asientos**

#### **Endpoint:** `POST /api/v1/journal-entries/{id}/post`
```json
// Request
{}

// Response 
{
  "data": {
    "type": "journal-entries",
    "id": "1",
    "attributes": {
      "status": "posted"  // Cambió de draft → posted
    }
  }
}
```

#### **UI Frontend:**
- **Botón "Postear":** Solo visible si `status === 'draft'`
- **Validación previa:** Backend valida balance y cuentas postables
- **Post-posteo:** Botones de edición desaparecen (inmutable)
- **Error handling:** Mostrar errores de validación backend

---

## 💰 **MÓDULO FINANCE (Finanzas)**

### **📄 Facturas de Proveedores (AP)**

#### **Endpoint:** `GET /api/v1/a-p-invoices?include=contact`
```json
{
  "data": [
    {
      "type": "a-p-invoices",
      "id": "1",
      "attributes": {
        "contactId": 5,
        "invoiceNumber": "FACT-001",
        "invoiceDate": "2025-08-20",
        "dueDate": "2025-09-20",
        "currency": "MXN",
        "exchangeRate": 1.00,
        "subtotal": 1000.00,
        "taxTotal": 160.00,
        "total": 1160.00,
        "status": "draft",
        "paidAmount": 0.00,
        "remainingBalance": 1160.00
      },
      "relationships": {
        "contact": {
          "data": {"type": "contacts", "id": "5"}
        }
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Listado:** Tabla con contact.name, invoiceNumber, invoiceDate, total, status, remainingBalance
- **Estados:** `draft` → `posted` → `paid` (colores: gris, azul, verde)
- **Filtros:** `?filter[status]=posted&filter[contact_id]=5`
- **Crear:** Form con contactId (dropdown suppliers), invoiceNumber, total básico

---

### **💳 Pagos a Proveedores (AP)**

#### **Endpoint:** `GET /api/v1/a-p-payments?include=aPInvoice,aPInvoice.contact`
```json
{
  "data": [
    {
      "type": "a-p-payments",
      "id": "1",
      "attributes": {
        "aPInvoiceId": 1,
        "bankAccountId": 1,
        "paymentDate": "2025-08-20",
        "amount": 1160.00,
        "paymentMethod": "transfer",
        "reference": "TXN-12345",
        "status": "draft"
      },
      "relationships": {
        "aPInvoice": {
          "data": {"type": "a-p-invoices", "id": "1"}
        }
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Crear desde factura:** Botón "Pagar" en detalle de AP Invoice
- **Form:** amount (≤ remainingBalance), bankAccountId, paymentDate, reference
- **Validación:** No permitir amount > saldo pendiente
- **Post-pago:** Estado de factura se actualiza automáticamente

---

### **📄 Facturas de Clientes (AR)**

#### **Endpoint:** `GET /api/v1/a-r-invoices?include=contact`
```json
{
  "data": [
    {
      "type": "a-r-invoices",
      "id": "1",
      "attributes": {
        "contactId": 10,
        "invoiceNumber": "INV-001", 
        "invoiceDate": "2025-08-20",
        "dueDate": "2025-09-20",
        "currency": "MXN",
        "subtotal": 2000.00,
        "taxTotal": 320.00,
        "total": 2320.00,
        "status": "posted",
        "collectedAmount": 500.00,
        "remainingBalance": 1820.00
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Similar a AP Invoice** pero para clientes
- **Crear:** contactId (dropdown customers), invoiceNumber, total
- **Estados:** Misma lógica draft → posted → paid

---

### **💰 Cobros de Clientes (AR)**

#### **Endpoint:** `GET /api/v1/a-r-receipts?include=aRInvoice,aRInvoice.contact`
```json
{
  "data": [
    {
      "type": "a-r-receipts",
      "id": "1", 
      "attributes": {
        "aRInvoiceId": 1,
        "bankAccountId": 1,
        "receiptDate": "2025-08-20",
        "amount": 500.00,
        "paymentMethod": "transfer",
        "reference": "DEP-67890",
        "status": "posted"
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Crear desde factura:** Botón "Cobrar" en detalle de AR Invoice
- **Form:** amount (≤ remainingBalance), bankAccountId, receiptDate
- **Similar a pagos** pero para ingresos

---

### **🏦 Cuentas Bancarias**

#### **Endpoint:** `GET /api/v1/bank-accounts`
```json
{
  "data": [
    {
      "type": "bank-accounts",
      "id": "1",
      "attributes": {
        "bankName": "BBVA",
        "accountNumber": "012345678901",
        "clabe": "012180001234567890",
        "currency": "MXN",
        "accountType": "checking",
        "openingBalance": 50000.00,
        "status": "active"
      }
    }
  ]
}
```

#### **UI Frontend:**
- **Catálogo simple:** bankName, accountNumber, currency, status
- **Uso:** Dropdown en formularios de pagos/cobros
- **⚠️ FASE 1:** Solo catálogo básico, SIN conciliación bancaria

---

## 🔄 **FLUJOS DE TRABAJO PRINCIPALES**

### **1. Flujo AP (Pagar a Proveedor)**
```
1. Crear AP Invoice (draft)
2. Postear AP Invoice (draft → posted) 
   → 🔄 Backend crea asiento GL automáticamente
3. Crear AP Payment desde invoice
4. Postear AP Payment (draft → posted)
   → 🔄 Backend crea asiento GL + actualiza estado invoice
```

### **2. Flujo AR (Facturar Cliente)**
```
1. Crear AR Invoice (draft)
2. Postear AR Invoice (draft → posted)
   → 🔄 Backend crea asiento GL automáticamente
3. Crear AR Receipt desde invoice  
4. Postear AR Receipt (draft → posted)
   → 🔄 Backend crea asiento GL + actualiza estado invoice
```

### **3. Flujo GL Manual**
```
1. Crear Journal Entry (draft)
2. Agregar Journal Lines (debit/credit)
3. Validar balance Σ(debit) = Σ(credit)
4. Postear Journal Entry (draft → posted → inmutable)
```

---

## ⚠️ **REGLAS DE NEGOCIO IMPORTANTES**

### **GL (Contabilidad)**
- Solo cuentas con `isPostable: true` en líneas de asiento
- Asientos deben balancear: Σ(debit) = Σ(credit)
- Estado `posted` = inmutable (no editable)

### **AP/AR (Finanzas)**  
- `invoiceNumber` debe ser único por contacto
- Pagos/cobros ≤ saldo pendiente de factura
- Estados se actualizan automáticamente post-pago

### **Posteo Automático**
- AP Invoice posteada → asiento GL (Gasto/Proveedor)
- AP Payment posteado → asiento GL (Proveedor/Banco) 
- AR Invoice posteada → asiento GL (Cliente/Ingreso)
- AR Receipt posteado → asiento GL (Banco/Cliente)

---

## 🚫 **LO QUE NO IMPLEMENTAR EN FASE 1**

```
❌ Multi-moneda (usar solo currency=MXN)
❌ Líneas detalladas de facturas (ap_invoice_lines)
❌ Conciliación bancaria (bank_statements)
❌ Aplicaciones complejas N:M (pivotes)
❌ Impuestos/retenciones avanzadas
❌ Aprobaciones intermedias
❌ Reversiones automáticas
❌ Secuencias automáticas de folios
```

---

## 🎨 **SUGERENCIAS UX/UI**

### **Estados Visuales**
- `draft`: Gris, "Borrador", botones de edición activos
- `posted`: Azul, "Contabilizado", inmutable 
- `paid`: Verde, "Pagado", proceso completo

### **Validaciones Frontend**
- Balance de asientos en tiempo real
- Saldo disponible en pagos/cobros
- Campos requeridos por endpoint

### **Navegación**
- Dashboard con resumen AP/AR pendientes
- Filtros rápidos por estado y fecha
- Links entre facturas ↔ pagos ↔ asientos GL

---

## 📱 **ENDPOINTS CRÍTICOS RESUMIDOS**

### **Contabilidad**
```
GET    /api/v1/accounts
GET    /api/v1/journal-entries?include=journalLines,journalLines.account
POST   /api/v1/journal-entries/{id}/post
```

### **Cuentas por Pagar**
```
GET    /api/v1/a-p-invoices?include=contact
POST   /api/v1/a-p-invoices/{id}/post
GET    /api/v1/a-p-payments?include=aPInvoice
POST   /api/v1/a-p-payments/{id}/post
```

### **Cuentas por Cobrar**
```
GET    /api/v1/a-r-invoices?include=contact  
POST   /api/v1/a-r-invoices/{id}/post
GET    /api/v1/a-r-receipts?include=aRInvoice
POST   /api/v1/a-r-receipts/{id}/post
```

### **Catálogos**
```
GET    /api/v1/bank-accounts
GET    /api/v1/contacts?filter[isSupplier]=1
GET    /api/v1/contacts?filter[isCustomer]=1
```

---

## ✅ **CHECKLIST IMPLEMENTACIÓN**

### **Contabilidad**
- [ ] Listado cuentas con filtro isPostable
- [ ] CRUD asientos contables básico
- [ ] Validación balance debit/credit  
- [ ] Botón postear con cambio estado visual
- [ ] Vista detalle con líneas incluidas

### **Cuentas por Pagar**
- [ ] CRUD facturas proveedores
- [ ] Form pago desde factura (validar saldo)
- [ ] Estados visuales draft/posted/paid
- [ ] Integración con contacts (suppliers)

### **Cuentas por Cobrar** 
- [ ] CRUD facturas clientes
- [ ] Form cobro desde factura 
- [ ] Estados visuales similares a AP
- [ ] Integración con contacts (customers)

### **Dashboard/Reportes Básicos**
- [ ] Resumen AP pendientes
- [ ] Resumen AR pendientes  
- [ ] Flujo efectivo básico (pagos vs cobros)

---

**🎯 RESULTADO ESPERADO:** Sistema contable-financiero funcional que maneje el ciclo completo de facturas, pagos y contabilización automática con UX simple y efectiva.

---

*Documento para equipo frontend | Backend ya implementado y funcional*