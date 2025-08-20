# 📊 REPORTE COMPLETO - Finance & Accounting Phase 1

## 🏛️ MÓDULOS IMPLEMENTADOS

### 📗 **FINANCE MODULE** (Módulo Financiero)
- **AP Invoices** (Facturas por Pagar)
- **AR Invoices** (Facturas por Cobrar)  
- **AP/AR Payments & Receipts** (Pagos y Recibos)
- **Bank Accounts & Statements** (Cuentas Bancarias y Estados)

### 📘 **ACCOUNTING MODULE** (Módulo Contable)
- **Chart of Accounts** (Plan Contable)
- **Journal Entries** (Asientos Contables)
- **Fiscal Periods** (Períodos Fiscales)
- **Exchange Rates** (Tipos de Cambio)

---

## 🔗 BASE URL
```
http://localhost:8000/api/v1/
```

---

## 🔐 AUTENTICACIÓN
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@example.com", 
  "password": "secureadmin"
}
```
**Response:** `{"access_token": "...", "token_type": "Bearer"}`

**Header requerido para todos los endpoints:**
```
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

---

## 📋 ENDPOINTS PRINCIPALES

### **AP INVOICES** `/a-p-invoices`

#### **GET /a-p-invoices** - List
**Filtros disponibles:**
- `filter[invoice_number]=FACT-001`
- `filter[currency]=MXN`
- `filter[status]=draft`
- `sort=invoice_date,total,-created_at`

#### **POST /a-p-invoices** - Create
**Campos REQUERIDOS:**
```json
{
  "data": {
    "type": "a-p-invoices",
    "attributes": {
      "contactId": "31",           // string, ID del proveedor
      "invoiceNumber": "FACT-001", // string, máx 255 chars, único por proveedor
      "invoiceDate": "2025-08-20", // date, formato YYYY-MM-DD
      "dueDate": "2025-09-20",     // date
      "subtotal": "100.00",        // string (decimal)
      "taxTotal": "16.00",         // string (decimal) 
      "total": "116.00",           // string (decimal)
      "status": "draft"            // string: draft|posted|paid
    }
  }
}
```

**Campos OPCIONALES:**
```json
{
  "currency": "MXN",          // string, default: MXN
  "exchangeRate": "20.00",    // string (decimal), default: null
  "metadata": {}              // object, datos adicionales
}
```

#### **PATCH /a-p-invoices/{id}** - Update
Mismos campos que POST, todos opcionales.

#### **GET /a-p-invoices/{id}** - Show
**Response incluye campos calculados:**
```json
{
  "data": {
    "type": "a-p-invoices",
    "id": "1",
    "attributes": {
      "contactId": 31,
      "invoiceNumber": "FACT-001",
      "invoiceDate": "2025-08-20T00:00:00.000000Z",
      "dueDate": "2025-09-20T00:00:00.000000Z",
      "currency": "MXN",
      "exchangeRate": "20.00",
      "subtotal": "100.00",
      "taxTotal": "16.00", 
      "total": "116.00",
      "status": "draft",
      
      // ✅ CAMPOS CALCULADOS (NUEVOS)
      "paidAmount": 0,           // float, suma de pagos aplicados
      "remainingBalance": 116.00, // float, total - paidAmount
      
      "createdAt": "2025-08-20T12:37:07.000000Z",
      "updatedAt": "2025-08-20T12:37:07.000000Z"
    },
    "relationships": {
      "aPInvoiceLines": {...},
      "aPInvoicePayments": {...}
    }
  }
}
```

### **AR INVOICES** `/a-r-invoices`

Estructura **IDÉNTICA** a AP Invoices, con:
- **Type:** `"a-r-invoices"`
- **Relationships:** `"aRInvoiceLines"`, `"aRInvoiceReceipts"`
- **Campos calculados:** `paidAmount`, `remainingBalance`

### **ACCOUNTS** `/accounts`

#### **GET /accounts** - List  
**Filtros disponibles:**
- `filter[code]=1100`
- `filter[name]=Banco`
- `filter[account_type]=asset`
- `filter[level]=1`
- `filter[currency]=MXN`
- `filter[is_postable]=true`
- `filter[status]=active`
- `sort=code,name,level`

#### **POST /accounts** - Create
**Campos REQUERIDOS:**
```json
{
  "data": {
    "type": "accounts",
    "attributes": {
      "code": "1100",              // string, único, máx 255
      "name": "Banco Principal",   // string, máx 255
      "accountType": "asset",      // string: asset|liability|equity|revenue|expense
      "level": 1,                  // integer, nivel jerárquico
      "isPostable": true,          // boolean
      "status": "active"           // string
    }
  }
}
```

**Campos OPCIONALES:**
```json
{
  "parentId": "1",        // string, ID cuenta padre
  "currency": "MXN",      // string
  "metadata": {}          // object
}
```

---

## 🏦 CUENTAS BÁSICAS PRE-CREADAS

```javascript
// Estas cuentas YA EXISTEN en la base de datos:
[
  {id: 1, code: "1100", name: "Banco", accountType: "asset"},
  {id: 2, code: "1200", name: "Clientes", accountType: "asset"}, 
  {id: 3, code: "2100", name: "Proveedores", accountType: "liability"},
  {id: 4, code: "5000", name: "Ingresos por Ventas", accountType: "revenue"},
  {id: 5, code: "4000", name: "Gastos Generales", accountType: "expense"}
]
```

---

## 📊 ESTRUCTURA DE RESPONSES JSON:API

### **Colecciones (Index)**
```json
{
  "jsonapi": {"version": "1.0"},
  "data": [...],
  "meta": {
    "page": {
      "currentPage": 1,
      "from": 1,
      "lastPage": 3,
      "perPage": 15,
      "to": 15,
      "total": 45
    }
  },
  "links": {
    "first": "http://localhost:8000/api/v1/accounts?page[number]=1",
    "last": "http://localhost:8000/api/v1/accounts?page[number]=3",
    "next": "http://localhost:8000/api/v1/accounts?page[number]=2"
  }
}
```

### **Recursos Individuales (Show)**
```json
{
  "jsonapi": {"version": "1.0"},
  "data": {
    "type": "resource-type",
    "id": "1", 
    "attributes": {...},
    "relationships": {...}
  },
  "links": {"self": "..."}
}
```

---

## ⚠️ VALIDACIONES Y ERRORES

### **Error 422 - Validation**
```json
{
  "jsonapi": {"version": "1.0"},
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "El campo Invoice number es obligatorio.",
      "source": {"pointer": "/data/attributes/invoiceNumber"}
    }
  ]
}
```

### **Error 401 - Unauthorized**
```json
{
  "jsonapi": {"version": "1.0"},
  "errors": [{
    "status": "401",
    "title": "Unauthorized", 
    "detail": "Unauthenticated."
  }]
}
```

---

## 🔄 RELACIONES DISPONIBLES

### **AP/AR Invoices**
- `?include=aPInvoiceLines` - Líneas de factura
- `?include=aPInvoicePayments` - Pagos aplicados

### **Accounts**
- Sin relaciones incluibles en Phase 1

---

## 💡 NOVEDADES IMPORTANTES

### ✅ **CAMPOS CALCULADOS**
Los campos `paidAmount` y `remainingBalance` ahora aparecen automáticamente en todas las responses de facturas.

### ✅ **FILTROS MEJORADOS**
Todos los endpoints soportan filtrado granular por cualquier campo.

### ✅ **PAGINACIÓN ESTÁNDAR**
Respuestas con meta.page y links estándar JSON:API.

### ✅ **VALIDACIÓN EN ESPAÑOL**
Mensajes de error en español para mejor UX.

---

## 🚀 STATUS CODES

- **200 OK** - Operación exitosa
- **201 Created** - Recurso creado
- **204 No Content** - Actualización/eliminación exitosa
- **401 Unauthorized** - Token inválido/ausente
- **403 Forbidden** - Sin permisos
- **404 Not Found** - Recurso no encontrado
- **422 Unprocessable Entity** - Errores de validación

---

## 📝 TESTING ENDPOINTS

### Ejemplo completo de uso:
```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' | \
  grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 2. Get AP Invoices
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/a-p-invoices"

# 3. Get specific AP Invoice with calculated fields
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/a-p-invoices/1"

# 4. Get Accounts
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/accounts"
```

---

**Fecha:** 2025-08-20  
**Status:** ✅ COMPLETADO  
**Versión:** Phase 1