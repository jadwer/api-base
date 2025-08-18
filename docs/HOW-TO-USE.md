# 📘 HOW TO USE - Guía Práctica para Humanos

**Una guía simple y directa para usar api-base desde cero**

> 🎯 **Objetivo:** Que puedas usar la API en 5 minutos, sin complicaciones.

---

## 📋 Tabla de Contenidos

1. [**Instalación Rápida**](#1-instalación-rápida) - Levantar el proyecto en 3 pasos
2. [**Tu Primer Login**](#2-tu-primer-login) - Obtener token de autenticación
3. [**Usar la API**](#3-usar-la-api) - CRUD básico con ejemplos reales
4. [**Filtros y Búsquedas**](#4-filtros-y-búsquedas) - Encontrar lo que necesitas
5. [**Relaciones**](#5-relaciones) - Incluir datos relacionados
6. [**Crear Tu Módulo**](#6-crear-tu-módulo) - Paso a paso desde cero
7. [**Testing**](#7-testing) - Probar que todo funciona
8. [**Casos de Uso Reales**](#8-casos-de-uso-reales) - Ejemplos del mundo real
9. [**Comandos Útiles**](#9-comandos-útiles) - Cheatsheet de comandos
10. [**Troubleshooting**](#10-troubleshooting) - Soluciones a problemas comunes

---

## 1. Instalación Rápida

### Opción A: Instalación Automática (Recomendada) 🚀

```bash
# 1. Clonar el proyecto
git clone https://github.com/tu-repo/api-base.git
cd api-base

# 2. Instalar todo y configurar
composer install
npm install
cp .env.example .env
php artisan key:generate

# 3. Configurar base de datos (edita .env con tus credenciales)
nano .env
# DB_DATABASE=api_base
# DB_USERNAME=tu_usuario
# DB_PASSWORD=tu_password

# 4. Crear base de datos y datos de ejemplo
php artisan migrate:fresh --seed

# 5. Iniciar el servidor
composer dev
```

**¡Listo! La API está corriendo en `http://localhost:8000`** 🎉

### Opción B: Docker (Si prefieres contenedores) 🐳

```bash
# 1. Clonar y entrar
git clone https://github.com/tu-repo/api-base.git
cd api-base

# 2. Levantar con Docker
docker-compose up -d

# 3. Instalar dependencias
docker-compose exec app composer install
docker-compose exec app php artisan migrate:fresh --seed

# Listo! En http://localhost:8080
```

### Usuarios de Prueba Creados

| Email | Password | Rol | Permisos |
|-------|----------|-----|----------|
| `admin@example.com` | `password` | Admin | Todo |
| `tech@example.com` | `password` | Técnico | Solo lectura |
| `customer@example.com` | `password` | Cliente | Limitado |

---

## 2. Tu Primer Login

### Usando cURL (Terminal)

```bash
# Login como admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### Respuesta Exitosa:
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  },
  "token": "1|kZkxjEn3NcKNwCnb2ePqSGPtJhUVRhGYkBqnHkpL"
}
```

**⚠️ IMPORTANTE: Guarda ese token, lo necesitarás para todo**

### Usando Postman/Insomnia

1. **Método:** POST
2. **URL:** `http://localhost:8000/api/auth/login`
3. **Headers:** `Content-Type: application/json`
4. **Body (JSON):**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

### Usando JavaScript/Axios

```javascript
// Login y guardar token
const response = await axios.post('http://localhost:8000/api/auth/login', {
  email: 'admin@example.com',
  password: 'password'
});

const token = response.data.token;
localStorage.setItem('api_token', token);

// Usar el token en siguientes requests
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

---

## 3. Usar la API

### 🔍 Listar Productos (GET)

```bash
# Listar todos los productos
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/vnd.api+json"
```

**Respuesta:**
```json
{
  "data": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "name": "Laptop Dell XPS 13",
        "sku": "LAP-001",
        "description": "Ultrabook profesional",
        "price": 1299.99,
        "cost": 900.00,
        "iva": true,
        "createdAt": "2024-01-15T10:30:00Z",
        "updatedAt": "2024-01-15T10:30:00Z"
      },
      "relationships": {
        "category": {
          "data": { "type": "categories", "id": "1" }
        },
        "brand": {
          "data": { "type": "brands", "id": "1" }
        }
      }
    }
  ],
  "meta": {
    "page": {
      "currentPage": 1,
      "from": 1,
      "lastPage": 5,
      "perPage": 15,
      "to": 15,
      "total": 75
    }
  }
}
```

### ➕ Crear Producto (POST)

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "products",
      "attributes": {
        "name": "iPhone 15 Pro",
        "sku": "IPH-015",
        "description": "El último iPhone",
        "price": 1199.00,
        "cost": 800.00,
        "iva": true
      },
      "relationships": {
        "category": {
          "data": { "type": "categories", "id": "2" }
        },
        "brand": {
          "data": { "type": "brands", "id": "3" }
        },
        "unit": {
          "data": { "type": "units", "id": "1" }
        }
      }
    }
  }'
```

### ✏️ Actualizar Producto (PATCH)

```bash
curl -X PATCH http://localhost:8000/api/v1/products/1 \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "products",
      "id": "1",
      "attributes": {
        "price": 999.99,
        "description": "Precio rebajado - Oferta especial"
      }
    }
  }'
```

### 🗑️ Eliminar Producto (DELETE)

```bash
curl -X DELETE http://localhost:8000/api/v1/products/1 \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---

## 4. Filtros y Búsquedas

### Filtrar por nombre
```bash
# Buscar productos que contengan "laptop"
GET /api/v1/products?filter[name]=laptop
```

### Filtrar por múltiples valores
```bash
# Productos con precio menor a 500
GET /api/v1/products?filter[price]=<500

# Productos activos
GET /api/v1/products?filter[isActive]=true

# Combinar filtros
GET /api/v1/products?filter[name]=laptop&filter[price]=<1000&filter[isActive]=true
```

### Ordenar resultados
```bash
# Ordenar por precio ascendente
GET /api/v1/products?sort=price

# Ordenar por precio descendente  
GET /api/v1/products?sort=-price

# Ordenar por múltiples campos
GET /api/v1/products?sort=-price,name
```

### Paginación
```bash
# Página 2, 10 items por página
GET /api/v1/products?page[number]=2&page[size]=10

# Solo 5 items
GET /api/v1/products?page[size]=5
```

---

## 5. Relaciones

### Incluir datos relacionados
```bash
# Producto con su categoría y marca
GET /api/v1/products/1?include=category,brand

# Todos los productos con sus categorías
GET /api/v1/products?include=category

# Orden de compra con items y proveedor
GET /api/v1/purchase-orders/1?include=supplier,purchaseOrderItems.product
```

### Ejemplo de respuesta con relaciones:
```json
{
  "data": {
    "type": "products",
    "id": "1",
    "attributes": {
      "name": "Laptop Dell XPS 13"
    },
    "relationships": {
      "category": {
        "data": { "type": "categories", "id": "1" }
      }
    }
  },
  "included": [
    {
      "type": "categories",
      "id": "1",
      "attributes": {
        "name": "Electrónica",
        "description": "Productos electrónicos"
      }
    }
  ]
}
```

---

## 6. Crear Tu Módulo

### Ejemplo: Crear módulo de "Proveedores" paso a paso

#### Paso 1: Generar módulo base
```bash
php artisan module:make Suppliers
```

#### Paso 2: Crear el archivo de configuración
```bash
# Crear archivo: suppliers-config.json
```

```json
{
  "entities": {
    "Supplier": {
      "name": "Supplier",
      "tableName": "suppliers",
      "fields": [
        {
          "name": "name",
          "type": "string",
          "required": true,
          "fillable": true,
          "sortable": true,
          "filterable": true
        },
        {
          "name": "email",
          "type": "string",
          "required": true,
          "fillable": true,
          "filterable": true
        },
        {
          "name": "phone",
          "type": "string",
          "required": false,
          "fillable": true
        },
        {
          "name": "address",
          "type": "text",
          "required": false,
          "fillable": true
        },
        {
          "name": "is_active",
          "type": "boolean",
          "required": false,
          "fillable": true,
          "default": true,
          "sortable": true,
          "filterable": true
        }
      ]
    }
  },
  "relationships": []
}
```

#### Paso 3: Generar todo automáticamente
```bash
php artisan module:advanced-blueprint Suppliers --config=suppliers-config.json
```

#### Paso 4: Migrar y poblar
```bash
php artisan migrate
php artisan db:seed --class="Modules\\Suppliers\\Database\\Seeders\\SuppliersDatabaseSeeder"
```

#### Paso 5: Probar
```bash
php artisan test Modules/Suppliers/Tests/Feature/
```

**¡Listo! Tu módulo está funcionando** 🎉

---

## 7. Testing

### Ejecutar todos los tests
```bash
php artisan test
```

### Tests de un módulo específico
```bash
# Solo tests del módulo Product
php artisan test Modules/Product/Tests/

# Solo tests de Inventory
php artisan test Modules/Inventory/Tests/
```

### Test específico
```bash
# Test de creación de productos
php artisan test --filter test_admin_can_create_product
```

### Ver cobertura
```bash
php artisan test --coverage
```

### Crear un test nuevo
```bash
# Ejemplo: Test para validar descuentos
php artisan module:make-test Feature/DiscountValidationTest Product
```

---

## 8. Casos de Uso Reales

### 📦 Sistema de Inventario

#### Crear almacén principal
```json
POST /api/v1/warehouses
{
  "data": {
    "type": "warehouses",
    "attributes": {
      "name": "Almacén Central",
      "code": "WH-001",
      "address": "Av. Principal 123",
      "capacity": 10000,
      "isActive": true
    }
  }
}
```

#### Registrar entrada de stock
```json
POST /api/v1/inventory-movements
{
  "data": {
    "type": "inventory-movements",
    "attributes": {
      "movementType": "purchase",
      "quantity": 100,
      "unitCost": 50.00,
      "notes": "Compra orden #PO-2024-001"
    },
    "relationships": {
      "product": {
        "data": { "type": "products", "id": "1" }
      },
      "warehouse": {
        "data": { "type": "warehouses", "id": "1" }
      }
    }
  }
}
```

### 🛒 E-commerce: Carrito de Compras

#### Agregar producto al carrito
```json
POST /api/v1/cart-items
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "quantity": 2,
      "price": 599.99
    },
    "relationships": {
      "shoppingCart": {
        "data": { "type": "shopping-carts", "id": "1" }
      },
      "product": {
        "data": { "type": "products", "id": "5" }
      }
    }
  }
}
```

#### Aplicar cupón de descuento
```json
PATCH /api/v1/shopping-carts/1
{
  "data": {
    "type": "shopping-carts",
    "id": "1",
    "relationships": {
      "coupon": {
        "data": { "type": "coupons", "id": "3" }
      }
    }
  }
}
```

### 💰 Ventas: Crear orden completa

#### Paso 1: Crear cliente
```json
POST /api/v1/customers
{
  "data": {
    "type": "customers",
    "attributes": {
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "phone": "+1234567890",
      "creditLimit": 5000.00
    }
  }
}
```

#### Paso 2: Crear orden de venta
```json
POST /api/v1/sales-orders
{
  "data": {
    "type": "sales-orders",
    "attributes": {
      "orderNumber": "SO-2024-001",
      "orderDate": "2024-01-15",
      "status": "pending",
      "subtotal": 2000.00,
      "tax": 360.00,
      "total": 2360.00
    },
    "relationships": {
      "customer": {
        "data": { "type": "customers", "id": "1" }
      }
    }
  }
}
```

#### Paso 3: Agregar items a la orden
```json
POST /api/v1/sales-order-items
{
  "data": {
    "type": "sales-order-items",
    "attributes": {
      "quantity": 2,
      "unitPrice": 1000.00,
      "subtotal": 2000.00
    },
    "relationships": {
      "salesOrder": {
        "data": { "type": "sales-orders", "id": "1" }
      },
      "product": {
        "data": { "type": "products", "id": "1" }
      }
    }
  }
}
```

---

## 9. Comandos Útiles

### 🚀 Desarrollo Diario

```bash
# Iniciar servidor de desarrollo (todo en uno)
composer dev

# Resetear base de datos con datos frescos
php artisan migrate:fresh --seed

# Ver todas las rutas disponibles
php artisan route:list

# Buscar rutas específicas
php artisan route:list | grep products

# Limpiar todos los cachés
php artisan optimize:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

### 🏗️ Generación de Código

```bash
# Crear nuevo módulo
php artisan module:make NombreModulo

# Generar módulo completo con configuración
php artisan module:advanced-blueprint NombreModulo --config=config.json

# Eliminar módulo completamente
php artisan module:force-delete NombreModulo

# Generar documentación API
php artisan api:generate-docs
```

### 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests con detalles
php artisan test --verbose

# Solo un archivo de test
php artisan test tests/Feature/ExampleTest.php

# Con coverage
php artisan test --coverage --min=80
```

### 📊 Base de Datos

```bash
# Crear migración
php artisan make:migration create_example_table

# Ejecutar migraciones pendientes
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status

# Ejecutar seeder específico
php artisan db:seed --class=ProductSeeder
```

---

## 10. Troubleshooting

### ❌ Error: "Unauthenticated"

**Problema:** No estás enviando el token o está mal formateado.

**Solución:**
```bash
# Formato correcto del header
Authorization: Bearer TU_TOKEN_AQUI

# NO uses:
Authorization: TU_TOKEN_AQUI  # Falta "Bearer"
Authorization: bearer TU_TOKEN_AQUI  # "bearer" debe ser "Bearer"
```

### ❌ Error: "This action is unauthorized" (403)

**Problema:** Tu usuario no tiene permisos.

**Solución:**
```bash
# Verificar permisos del usuario
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions();

# Asignar permiso faltante
>>> $user->givePermissionTo('products.store');
```

### ❌ Error: "The given data was invalid" (422)

**Problema:** Validación falló.

**Solución:** Revisa los campos requeridos:
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The name field is required.",
      "source": {
        "pointer": "/data/attributes/name"
      }
    }
  ]
}
```

### ❌ Error: "Class not found"

**Problema:** Falta actualizar autoload.

**Solución:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### ❌ Tests fallan después de cambios

**Problema:** Base de datos de test desactualizada.

**Solución:**
```bash
# Resetear base de datos de test
php artisan migrate:fresh --seed --env=testing

# O agregar en cada test
$this->refreshDatabase();
```

### ❌ Módulo no aparece en rutas

**Problema:** No está registrado en Server.php.

**Solución:**
1. Editar `app/JsonApi/V1/Server.php`
2. Agregar schemas en `allSchemas()`
3. Agregar authorizers en `authorizers()`
4. Ejecutar `php artisan optimize:clear`

---

## 📚 Recursos Adicionales

### Documentación Relacionada
- [**module-blueprint-master.md**](./development/module-blueprint-master.md) - Guía técnica completa
- [**CLAUDE.md**](../CLAUDE.md) - Documentación del proyecto
- [**JSON:API Spec**](https://jsonapi.org/) - Especificación oficial

### Herramientas Recomendadas
- **Postman** - Para probar APIs
- **TablePlus** - Para ver la base de datos
- **VS Code** - Con extensiones de Laravel
- **Laravel Debugbar** - Para debugging

### Ejemplos de Proyectos
- Sistema de Inventario completo
- E-commerce con carrito
- CRM con gestión de clientes
- ERP modular

---

## 🎓 Tips Finales

1. **Siempre usa tokens Bearer** en el header Authorization
2. **Content-Type debe ser** `application/vnd.api+json` para JSON:API
3. **Los campos van en camelCase** en la API (isActive, createdAt)
4. **Los campos van en snake_case** en la base de datos (is_active, created_at)
5. **Prueba primero con Postman** antes de integrar al frontend
6. **Lee los tests** para entender cómo funciona cada endpoint
7. **Usa los seeders** para tener datos de prueba consistentes
8. **Revisa los logs** cuando algo falle (`storage/logs/laravel.log`)

---

**¿Preguntas?** Revisa primero el [Troubleshooting](#10-troubleshooting) o los tests del módulo que uses.

**¡Happy Coding!** 🚀