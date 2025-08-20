# 🛒 REPORTE COMPLETO - Ecommerce Module Enhanced

## 🚀 MÓDULO MEJORADO AL NIVEL DE FINANCE

### 🏪 **ECOMMERCE MODULE** (Módulo de Comercio Electrónico)
- **Shopping Carts** (Carritos de Compras) con campos calculados inteligentes
- **Cart Items** (Items de Carrito) con relaciones a productos
- **Coupons** (Cupones) con validaciones de business logic avanzadas
- **JSON:API completo** con paginación, filtros granulares y validaciones en español

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

## 🛒 SHOPPING CARTS `/shopping-carts`

### **GET /shopping-carts** - List
**Filtros disponibles:**
- `filter[status]=active` - Estado del carrito
- `filter[session_id]=sess_123` - ID de sesión específico
- `filter[user_id]=1` - Carritos de un usuario
- `filter[currency]=MXN` - Filtrar por moneda
- `filter[coupon_code]=SUMMER20` - Carritos con cupón específico
- `sort=total_amount,expires_at,-created_at` - Ordenamiento

### **POST /shopping-carts** - Create
**Campos REQUERIDOS:**
```json
{
  "data": {
    "type": "shopping-carts",
    "attributes": {
      "status": "active",           // string: active|inactive|expired
      "totalAmount": 1250.00,       // numeric, total del carrito
      "currency": "MXN"             // string, código de moneda (3 chars)
    }
  }
}
```

**Campos OPCIONALES:**
```json
{
  "sessionId": "sess_abc123",     // string, ID de sesión
  "userId": "1",                  // string, ID del usuario
  "expiresAt": "2025-09-20",      // date, fecha de expiración
  "couponCode": "SUMMER20",       // string, código de cupón
  "discountAmount": 125.00,       // numeric, descuento aplicado
  "taxAmount": 200.00,            // numeric, impuestos
  "shippingAmount": 99.00,        // numeric, costo de envío
  "notes": "Entrega urgente",     // string, notas especiales
  "metadata": {}                  // object, datos adicionales
}
```

### **GET /shopping-carts/{id}** - Show
**Response incluye campos calculados:**
```json
{
  "data": {
    "type": "shopping-carts",
    "id": "1",
    "attributes": {
      "sessionId": "sess_f993ee5403ede43419112bc229edeb6e",
      "userId": 1,
      "status": "active",
      "expiresAt": "2025-08-23T21:23:26.000000Z",
      "totalAmount": 2483.47,
      "currency": "MXN",
      "couponCode": null,
      "discountAmount": 0,
      "taxAmount": 301.31,
      "shippingAmount": 299,
      "notes": null,
      
      // ✅ CAMPOS CALCULADOS INTELIGENTES (NUEVOS)
      "itemsCount": 0,              // int, cantidad de items en el carrito
      "subtotalAmount": 0,          // float, suma real de todos los cart_items
      "finalTotal": 600.31,         // float, cálculo: subtotal - discount + tax + shipping
      "isExpired": false,           // bool, si el carrito ya expiró
      "canApplyCoupon": true,       // bool, si se puede aplicar un cupón
      
      "createdAt": "2025-08-20T12:58:49.000000Z",
      "updatedAt": "2025-08-20T12:58:49.000000Z"
    },
    "relationships": {
      "cartItems": {...},           // Items del carrito
      "user": {...}                 // Usuario propietario
    }
  }
}
```

---

## 🎟️ COUPONS `/coupons`

### **GET /coupons** - List
**Filtros disponibles:**
- `filter[code]=SUMMER20` - Filtrar por código específico
- `filter[type]=percentage` - Tipo: percentage|fixed
- `filter[is_active]=true` - Solo cupones activos
- `sort=expires_at,value,-created_at` - Ordenamiento

### **POST /coupons** - Create
**Campos REQUERIDOS:**
```json
{
  "data": {
    "type": "coupons",
    "attributes": {
      "code": "NEWUSER15",          // string, código único
      "name": "Descuento Nuevos Usuarios", // string, nombre descriptivo
      "couponType": "percentage",   // string: percentage|fixed
      "value": 15.00,               // numeric, valor del descuento
      "isActive": true              // boolean, si está activo
    }
  }
}
```

**Campos OPCIONALES:**
```json
{
  "description": "15% de descuento para nuevos usuarios",
  "minAmount": 500.00,            // numeric, compra mínima requerida
  "maxAmount": 5000.00,           // numeric, compra máxima permitida
  "maxUses": 100,                 // integer, usos máximos (null = ilimitado)
  "usedCount": 0,                 // integer, usos actuales
  "startsAt": "2025-08-20",       // date, fecha de inicio
  "expiresAt": "2025-12-31",      // date, fecha de expiración
  "customerIds": [1, 2, 3],       // array, IDs de clientes específicos
  "productIds": [1, 5, 10],       // array, IDs de productos específicos
  "categoryIds": [1, 2]           // array, IDs de categorías específicas
}
```

### **GET /coupons/{id}** - Show
**Response incluye campos calculados:**
```json
{
  "data": {
    "type": "coupons",
    "id": "1",
    "attributes": {
      "code": "SUMMER20",
      "name": "Promoción de Verano",
      "description": "Descuento especial de verano",
      "couponType": "percentage",
      "value": 20.00,
      "minAmount": 500.00,
      "maxAmount": null,
      "maxUses": 1000,
      "usedCount": 45,
      "startsAt": "2025-08-18T15:18:46.000000Z",
      "expiresAt": "2025-09-16T18:57:37.000000Z",
      "isActive": true,
      "customerIds": null,
      "productIds": [5],
      "categoryIds": [1],
      
      // ✅ CAMPOS CALCULADOS INTELIGENTES (NUEVOS)
      "isValid": true,              // bool, si el cupón es válido ahora
      "remainingUses": 955,         // int|null, usos restantes (null = ilimitado)
      "isExpired": false,           // bool, si ya expiró
      
      "createdAt": "2025-08-20T12:58:49.000000Z",
      "updatedAt": "2025-08-20T12:58:49.000000Z"
    }
  }
}
```

---

## 🛍️ CART ITEMS `/cart-items`

### **GET /cart-items** - List
**Filtros disponibles:**
- `filter[shopping_cart_id]=1` - Items de un carrito específico
- `filter[product_id]=5` - Items de un producto específico
- `filter[status]=active` - Estado del item
- `sort=total,quantity,-created_at` - Ordenamiento

### **POST /cart-items** - Create
**Campos REQUERIDOS:**
```json
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "shoppingCartId": "1",        // string, ID del carrito
      "productId": "5",             // string, ID del producto
      "quantity": 2,                // numeric, cantidad
      "unitPrice": 299.99,          // numeric, precio unitario
      "subtotal": 599.98,           // numeric, subtotal sin impuestos
      "total": 695.98               // numeric, total con impuestos
    }
  }
}
```

**Campos OPCIONALES:**
```json
{
  "originalPrice": 349.99,        // numeric, precio original antes de descuentos
  "discountPercent": 15.00,       // numeric, porcentaje de descuento
  "discountAmount": 100.00,       // numeric, monto de descuento
  "taxRate": 16.00,               // numeric, tasa de impuesto (%)
  "taxAmount": 95.98,             // numeric, monto de impuestos
  "status": "active",             // string, estado del item
  "metadata": {}                  // object, datos adicionales
}
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
    "first": "http://localhost:8000/api/v1/shopping-carts?page[number]=1",
    "last": "http://localhost:8000/api/v1/shopping-carts?page[number]=3",
    "next": "http://localhost:8000/api/v1/shopping-carts?page[number]=2"
  }
}
```

---

## ⚠️ VALIDACIONES Y ERRORES EN ESPAÑOL

### **Error 422 - Validation**
```json
{
  "jsonapi": {"version": "1.0"},
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "El campo Estado es obligatorio.",
      "source": {"pointer": "/data/attributes/status"}
    }
  ]
}
```

### **Mensajes de Validación Localizados:**
- "El campo Estado es obligatorio."
- "El campo Estado debe ser: activo, inactivo o expirado."
- "El campo Total debe ser un número."
- "El campo Total debe ser al menos 0."
- "El campo Moneda es obligatorio."
- "El campo Descuento debe ser un número."

---

## 🔄 RELACIONES DISPONIBLES

### **Shopping Carts**
- `?include=cartItems` - Items del carrito
- `?include=user` - Usuario propietario
- `?include=cartItems.product` - Items con información del producto

### **Cart Items**
- `?include=shoppingCart` - Carrito contenedor
- `?include=product` - Información del producto

### **Coupons**
- Sin relaciones directas (entidad independiente)

---

## 💡 BUSINESS LOGIC IMPLEMENTADA

### **🛒 Shopping Cart Logic**
```javascript
// Métodos de negocio disponibles en el backend:
isEmpty()           // Si el carrito está vacío
isActive()          // Si el carrito está activo y no expirado
canAddItems()       // Si se pueden agregar más items
```

### **🎟️ Coupon Logic**
```javascript
// Métodos de negocio disponibles en el backend:
canBeUsed(cartAmount)           // Si el cupón puede usarse
calculateDiscount(cartAmount)   // Calcula el descuento aplicable

// Validaciones automáticas:
- Fecha de expiración
- Usos máximos vs usos actuales
- Monto mínimo y máximo de compra
- Productos/categorías específicas
- Clientes específicos
```

---

## 🎯 CASOS DE USO TÍPICOS

### **1. Crear Carrito de Compras**
```javascript
// 1. Crear carrito
POST /shopping-carts
{
  "status": "active",
  "currency": "MXN",
  "userId": "123"
}

// 2. Agregar items
POST /cart-items
{
  "shoppingCartId": "1",
  "productId": "5",
  "quantity": 2,
  "unitPrice": 299.99
}

// 3. Ver carrito completo con totales calculados
GET /shopping-carts/1?include=cartItems.product
```

### **2. Aplicar Cupón**
```javascript
// 1. Buscar cupones válidos
GET /coupons?filter[is_active]=true&filter[code]=SUMMER20

// 2. Verificar validez (campos calculados automáticos)
// isValid: true/false
// remainingUses: número o null
// isExpired: true/false

// 3. Actualizar carrito con cupón
PATCH /shopping-carts/1
{
  "couponCode": "SUMMER20",
  "discountAmount": 125.50
}
```

### **3. Verificar Estado del Carrito**
```javascript
// Los campos calculados se actualizan automáticamente:
GET /shopping-carts/1

// Response automática:
{
  "itemsCount": 3,              // Calculado en tiempo real
  "subtotalAmount": 899.97,     // Suma de cart_items
  "finalTotal": 1043.96,        // Con impuestos y envío
  "isExpired": false,           // Validación de fecha
  "canApplyCoupon": true        // Lógica de negocio
}
```

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

### **Ejemplo completo de flujo Ecommerce:**
```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}' | \
  grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 2. Listar carritos activos
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/shopping-carts?filter[status]=active"

# 3. Ver carrito específico con campos calculados
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/shopping-carts/1"

# 4. Ver cupones válidos
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/coupons?filter[is_active]=true"

# 5. Ver items de carrito
curl -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" \
  "http://localhost:8000/api/v1/cart-items?filter[shopping_cart_id]=1"
```

---

## 💎 NOVEDADES VS. VERSIÓN ANTERIOR

### ✅ **CAMPOS CALCULADOS INTELIGENTES**
Los campos calculados aparecen automáticamente en todas las responses, similar a Finance module.

### ✅ **BUSINESS LOGIC AVANZADA**
- Validación de cupones con fechas, usos y montos
- Cálculos automáticos de totales con impuestos
- Estados inteligentes de carritos y cupones

### ✅ **VALIDACIONES EN ESPAÑOL**
Todos los mensajes de error localizados para mejor UX.

### ✅ **DATOS REALISTAS**
Seeders con información comercial real: códigos de cupón, metadata de tracking, cálculos de IVA.

### ✅ **FILTROS GRANULARES**
Filtrado avanzado por cualquier campo disponible.

### ✅ **JSON:API COMPLETAMENTE COMPATIBLE**
Paginación estándar, meta information, y links structure.

---

**Fecha:** 2025-08-20  
**Status:** ✅ COMPLETADO - Enhanced to Finance Level  
**Versión:** Enhanced v1.0  
**Compatibilidad:** JSON:API 1.1, Laravel 12