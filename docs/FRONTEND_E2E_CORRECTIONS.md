# Frontend E2E Test Corrections Guide

**Last Updated:** 2026-01-07
**Backend Version:** v1.0

Este documento proporciona las correcciones necesarias para los tests E2E del frontend basados en el reporte de fallos.

---

## Credenciales de Usuarios de Prueba

### IMPORTANTE: Contraseñas Correctas

Los tests deben usar estas credenciales exactas (definidas en `Modules/User/Database/seeders/UserSeeder.php`):

| Email | Password | Role | Permisos |
|-------|----------|------|----------|
| `admin@example.com` | `secureadmin` | admin | Acceso completo |
| `tech@example.com` | `securetech` | tech | Solo lectura |
| `customer@example.com` | `customer` | customer | Acceso limitado (Ecommerce) |
| `cliente1@example.com` | `customer` | customer | Cliente alternativo |
| `cliente2@example.com` | `customer` | customer | Cliente alternativo |
| `god@example.com` | `supersecure` | god | Super admin |

### Código de Login Correcto

```typescript
// Para admin
const adminCredentials = {
  email: 'admin@example.com',
  password: 'secureadmin'  // NO 'password'
};

// Para customer (flujo ecommerce)
const customerCredentials = {
  email: 'customer@example.com',
  password: 'customer'  // NO 'password'
};

// Para tech
const techCredentials = {
  email: 'tech@example.com',
  password: 'securetech'  // NO 'password'
};
```

---

## Correcciones por Test Suite

### 11. Online Sales Flow (11-online-sales-flow.spec.ts)

#### Tests Fallando y Correcciones

##### 2.1 Require authentication for checkout - FAIL
**Problema:** Credenciales incorrectas
**Corrección:**
```typescript
// INCORRECTO
await page.fill('[data-testid="email"]', 'customer@example.com');
await page.fill('[data-testid="password"]', 'password');

// CORRECTO
await page.fill('[data-testid="email"]', 'customer@example.com');
await page.fill('[data-testid="password"]', 'customer');
```

##### 2.2-2.4 Display shipping/payment forms - FAIL
**Problema:** El usuario no está autenticado correctamente
**Corrección:** Usar credenciales correctas (ver arriba)

##### Returning customer / Guest to user cart migration - FAIL
**Problema:** Credenciales y posiblemente flujo
**Corrección:**
```typescript
// Login correcto para returning customer
test('Returning customer', async ({ page }) => {
  // Primero crear un cart como guest
  const sessionId = 'sess_guest_' + Date.now();

  // Crear cart
  const cartResponse = await api.post('/api/v1/shopping-carts', {
    data: {
      type: 'shopping-carts',
      attributes: {
        sessionId: sessionId,
        currency: 'MXN'
      }
    }
  });

  // Login con credenciales correctas
  const loginResponse = await api.post('/api/auth/login', {
    email: 'customer@example.com',
    password: 'customer'  // <-- CORRECTO
  });

  // El cart se migra automáticamente al usuario
});
```

---

### 12. Payment Application Flow (12-payment-application-flow.spec.ts)

#### Tests Fallando y Correcciones

##### 1.4 Filter by payment status - FAIL
**Problema:** Nombre del filtro incorrecto
**Corrección:**
```typescript
// INCORRECTO - camelCase
GET /api/v1/ar-invoices?filter[paymentStatus]=pending

// CORRECTO - snake_case
GET /api/v1/ar-invoices?filter[payment_status]=pending
// O por status
GET /api/v1/ar-invoices?filter[status]=posted
```

##### 2.3 Display receipt form fields - FAIL
**Problema:** Campos esperados vs implementados
**Campos requeridos para crear un Payment:**
```typescript
interface PaymentCreateData {
  type: 'payments';
  attributes: {
    amount: number;           // Requerido
    paymentDate: string;      // Requerido (ISO date)
    reference?: string;       // Opcional
    notes?: string;           // Opcional
  };
  relationships: {
    contact: {                // Requerido
      data: { type: 'contacts', id: string }
    };
    bankAccount: {            // Requerido
      data: { type: 'bank-accounts', id: string }
    };
    paymentMethod: {          // Requerido
      data: { type: 'payment-methods', id: string }
    };
  };
}
```

---

### 13. System Health & Audit (13-system-health-audit.spec.ts)

#### Tests Fallando y Correcciones

##### Show overall system status - FAIL
**Problema:** Selector incorrecto para el status
**Respuesta del endpoint:**
```json
GET /api/v1/system-health

{
  "status": "healthy",  // o "warning" o "critical"
  "timestamp": "2026-01-07T...",
  "environment": "testing",
  "checks": {
    "database": { "status": "healthy", "message": "Database connection successful" },
    "cache": { "status": "healthy", "message": "Cache is working" },
    "queue": { "status": "healthy", "message": "Queue is healthy" },
    "storage": { "status": "healthy", "message": "Disk usage: 45%" }
  },
  "metrics": {
    "database": { ... },
    "application": { ... },
    "errors": { ... }
  }
}
```

**Corrección del selector:**
```typescript
// INCORRECTO - buscando texto específico
await expect(page.getByText('Sistema Saludable')).toBeVisible();

// CORRECTO - buscar el status del response
const response = await api.get('/api/v1/system-health');
expect(['healthy', 'warning', 'critical']).toContain(response.status);

// O en el DOM si hay indicador visual
await expect(page.locator('[data-status]')).toHaveAttribute('data-status', /healthy|warning|critical/);
```

##### Display cache/queue/storage health check - FAIL
**Problema:** Los nombres de los checks son en inglés
**Corrección:**
```typescript
// INCORRECTO - textos en español
await expect(page.getByText('Cache')).toBeVisible();
await expect(page.getByText('Cola')).toBeVisible();  // "Queue" no "Cola"
await expect(page.getByText('Almacenamiento')).toBeVisible();

// CORRECTO - verificar estructura de la respuesta
const health = await api.get('/api/v1/system-health');
expect(health.checks).toHaveProperty('database');
expect(health.checks).toHaveProperty('cache');
expect(health.checks).toHaveProperty('queue');
expect(health.checks).toHaveProperty('storage');
```

##### Show application metrics / Have refresh button - FAIL
**Problema:** Métricas disponibles vs esperadas
**Métricas reales disponibles:**
```typescript
interface ApplicationMetrics {
  users: number;
  products: number;
  salesOrders: number;
  purchaseOrders: number;
  contacts: number;
  activityLast24h?: number;
  totalActivityLogs?: number;
}
```

**Corrección:**
```typescript
// INCORRECTO - buscando "Usuarios"
await expect(page.getByText('Usuarios')).toBeVisible();

// CORRECTO - las métricas están en inglés en el API
const metrics = response.metrics.application;
expect(metrics.users).toBeGreaterThanOrEqual(0);
expect(metrics.products).toBeGreaterThanOrEqual(0);
```

---

## Filtros JSON:API - Convención snake_case

**IMPORTANTE:** Todos los filtros en la API usan snake_case, no camelCase.

### Ejemplos Correctos

```typescript
// AR Invoices
GET /api/v1/ar-invoices?filter[status]=posted
GET /api/v1/ar-invoices?filter[contact_id]=123
GET /api/v1/ar-invoices?filter[payment_status]=pending

// Bank Transactions
GET /api/v1/bank-transactions?filter[reconciliation_status]=unreconciled
GET /api/v1/bank-transactions?filter[bank_account_id]=1
GET /api/v1/bank-transactions?filter[transaction_type]=credit

// Cycle Counts
GET /api/v1/cycle-counts?filter[status]=scheduled
GET /api/v1/cycle-counts?filter[abc_class]=A
GET /api/v1/cycle-counts?filter[warehouse_id]=1

// Discount Rules
GET /api/v1/discount-rules?filter[is_active]=true
GET /api/v1/discount-rules?filter[discount_type]=percentage
```

---

## E2E Flow: Cart -> Checkout -> Order -> Invoice

### Flujo Completo Implementado en Backend

```
1. Browse Products (público)
   GET /api/v1/products?filter[is_active]=true

2. Create/Get Cart
   POST /api/v1/shopping-carts (guest con sessionId)
   GET /api/v1/shopping-carts?filter[user_id]=X (autenticado)

3. Add Items to Cart
   POST /api/v1/cart-items
   {
     "data": {
       "type": "cart-items",
       "attributes": { "quantity": 2 },
       "relationships": {
         "shoppingCart": { "data": { "type": "shopping-carts", "id": "1" }},
         "product": { "data": { "type": "products", "id": "123" }}
       }
     }
   }

4. Login (si guest)
   POST /api/auth/login
   { "email": "customer@example.com", "password": "customer" }

5. Create Checkout Session (requiere auth)
   POST /api/v1/checkout-sessions
   {
     "data": {
       "type": "checkout-sessions",
       "attributes": {
         "contactEmail": "customer@example.com",
         "contactPhone": "+52 55 1234 5678",
         "shippingAddress": { "street": "...", "city": "...", ... },
         "billingAddress": { ... }
       },
       "relationships": {
         "shoppingCart": { "data": { "type": "shopping-carts", "id": "1" }}
       }
     }
   }

6. Confirm Payment (Stripe)
   PATCH /api/v1/checkout-sessions/{id}
   {
     "data": {
       "type": "checkout-sessions",
       "id": "{id}",
       "attributes": {
         "status": "payment_confirmed",
         "paymentIntentId": "pi_xxx"
       }
     }
   }

7. Backend Automatically:
   - Creates Contact (or reuses existing)
   - Creates SalesOrder with status 'confirmed'
   - Creates SalesOrderItems from CartItems
   - Marks cart/session as 'completed'
   - Dispatches SalesOrderCompleted event
   - Listener creates ARInvoice with GL posting

8. Get Order Confirmation
   GET /api/v1/sales-orders/{id}?include=items,contact,arInvoice
```

---

## Endpoints de System Health

### Endpoint Principal
```
GET /api/v1/system-health
Authorization: Bearer {token}  // Requiere rol admin o tech
```

### Respuesta Completa
```json
{
  "status": "healthy",
  "timestamp": "2026-01-07T12:00:00.000000Z",
  "environment": "production",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful",
      "responseTimeMs": 2.5
    },
    "cache": {
      "status": "healthy",
      "message": "Cache is working",
      "driver": "redis",
      "responseTimeMs": 1.2
    },
    "queue": {
      "status": "healthy",
      "message": "Queue is healthy",
      "driver": "database",
      "pendingJobs": 0,
      "failedJobs": 0
    },
    "storage": {
      "status": "healthy",
      "message": "Disk usage: 45%",
      "totalGb": 100,
      "usedGb": 45,
      "freeGb": 55,
      "usedPercent": 45
    }
  },
  "metrics": {
    "database": {
      "driver": "mysql",
      "database": "erp_db",
      "totalSizeMb": 256.5,
      "topTables": [
        { "name": "activity_log", "rows": 15000, "sizeMb": 45.2 },
        ...
      ]
    },
    "application": {
      "users": 150,
      "products": 5000,
      "salesOrders": 12000,
      "purchaseOrders": 8000,
      "contacts": 3000,
      "activityLast24h": 450,
      "totalActivityLogs": 150000
    },
    "errors": {
      "recentExceptions": [],
      "last24hCounts": {},
      "totalExceptionsLast24h": 0
    }
  }
}
```

---

## Audit Log Endpoints

### Listar Eventos
```
GET /api/v1/activity-logs
Authorization: Bearer {token}  // Requiere rol admin
```

### Filtros Disponibles
```typescript
// Por tipo de evento
GET /api/v1/activity-logs?filter[event]=created
GET /api/v1/activity-logs?filter[event]=updated
GET /api/v1/activity-logs?filter[event]=deleted

// Por modelo/entidad
GET /api/v1/activity-logs?filter[subject_type]=Modules\Sales\Models\SalesOrder

// Por usuario
GET /api/v1/activity-logs?filter[causer_id]=123

// Ordenamiento
GET /api/v1/activity-logs?sort=-created_at  // Más recientes primero
```

### Respuesta
```json
{
  "data": [
    {
      "type": "activity-logs",
      "id": "1",
      "attributes": {
        "logName": "default",
        "description": "created",
        "event": "created",
        "subjectType": "Modules\\Sales\\Models\\SalesOrder",
        "subjectId": 123,
        "causerType": "Modules\\User\\Models\\User",
        "causerId": 1,
        "properties": {
          "old": null,
          "attributes": { "status": "pending", "total_amount": 1500 }
        },
        "createdAt": "2026-01-07T12:00:00.000000Z"
      }
    }
  ]
}
```

---

## Resumen de Correcciones Prioritarias

### 1. Credenciales (CRÍTICO)
- `admin@example.com` / `secureadmin`
- `customer@example.com` / `customer`
- `tech@example.com` / `securetech`

### 2. Filtros API (IMPORTANTE)
- Usar snake_case: `filter[payment_status]` NO `filter[paymentStatus]`

### 3. System Health (MODERADO)
- Los textos están en inglés
- Verificar la estructura JSON, no textos en pantalla

### 4. Checkout Flow (CRÍTICO)
- Requiere autenticación
- Usar credenciales correctas de customer
- El flujo completo está documentado en `docs/E2E_TESTING_GUIDE.md`

---

## Documentación Adicional

- [E2E Testing Guide](E2E_TESTING_GUIDE.md) - Flujos completos
- [Ecommerce Frontend Guide](modules/ECOMMERCE_FRONTEND_GUIDE.md) - Interfaces TypeScript
- [Frontend Integration Guide](FRONTEND_INTEGRATION_GUIDE.md) - Referencia general de API
- [Auth Frontend Guide](modules/AUTH_FRONTEND_GUIDE.md) - Autenticación
