# 🚀 Advanced Blueprint Generator - Guía Completa

**Generador automático de módulos complejos con múltiples entidades y relaciones**

> 🎯 **Objetivo:** Generar módulos completos en segundos, no horas.

---

## 📋 Tabla de Contenidos

1. [**Introducción**](#1-introducción) - Qué es y para qué sirve
2. [**Comandos Disponibles**](#2-comandos-disponibles) - Todos los comandos relacionados
3. [**Configuración JSON**](#3-configuración-json) - Estructura del archivo de configuración
4. [**Uso Básico**](#4-uso-básico) - Ejemplos simples paso a paso
5. [**Casos de Uso Avanzados**](#5-casos-de-uso-avanzados) - Módulos complejos con relaciones
6. [**Eliminar Módulos**](#6-eliminar-módulos) - Limpieza completa y segura
7. [**Banderas y Opciones**](#7-banderas-y-opciones) - Todas las opciones disponibles
8. [**Ejemplos Reales**](#8-ejemplos-reales) - Configuraciones de módulos existentes
9. [**Troubleshooting**](#9-troubleshooting) - Soluciones a problemas comunes
10. [**Tips y Mejores Prácticas**](#10-tips-y-mejores-prácticas) - Recomendaciones

---

## 1. Introducción

### ¿Qué es el Advanced Blueprint Generator?

Es una herramienta que **genera módulos completos automáticamente** basándose en una configuración JSON. En lugar de crear manualmente cada archivo (Models, Controllers, Schemas, Tests, etc.), defines la estructura en un archivo JSON y el generador crea todo por ti.

### ¿Qué genera automáticamente?

✅ **Models** con relaciones y casts  
✅ **Migrations** con índices y constraints  
✅ **JSON:API Schemas** con fields y filtros  
✅ **Controllers** con Actions traits  
✅ **Authorizers** con permisos granulares  
✅ **Requests** con validaciones  
✅ **Resources** completos  
✅ **Factories** con datos realistas  
✅ **Seeders** con permisos y datos  
✅ **Tests** completos (15+ tests por entidad)  
✅ **Integración** con Server.php y DatabaseSeeder  

### ¿Cuándo usarlo?

- ✅ Crear módulos con múltiples entidades relacionadas
- ✅ Proyectos que requieren estructura consistente
- ✅ Cuando necesitas tests completos desde el inicio
- ✅ Módulos con relaciones complejas entre entidades
- ✅ Prototipado rápido de APIs

---

## 2. Comandos Disponibles

### Comandos Principales

```bash
# 🚀 COMANDO PRINCIPAL: Generar módulo completo
php artisan module:advanced-blueprint {module} --config={archivo.json}

# 🗑️ ELIMINAR: Borrar módulo completamente
php artisan module:force-delete {module}

# 📋 LISTAR: Ver todos los módulos
php artisan module:list

# ✅ VALIDAR: Verificar estructura del módulo
php artisan module:validate {module}
```

### Comandos de Soporte

```bash
# Migrar módulo específico
php artisan module:migrate {module}

# Seeder de módulo específico
php artisan module:seed {module}

# Ver documentación del módulo
php artisan module:docs {module}

# Habilitar/Deshabilitar módulo
php artisan module:enable {module}
php artisan module:disable {module}
```

### Comandos de Desarrollo

```bash
# Ver status de migraciones por módulo
php artisan module:migrate-status

# Rollback migraciones del módulo
php artisan module:migrate-rollback {module}

# Refrescar módulo (rollback + migrate)
php artisan module:migrate-refresh {module}

# Actualizar autoload de módulos
php artisan module:dump
```

---

## 3. Configuración JSON

### Estructura Base

```json
{
  "entities": {
    "EntityName": {
      "name": "EntityName",
      "tableName": "entity_names",
      "fields": [
        {
          "name": "field_name",
          "type": "string|integer|boolean|decimal|date|text|json",
          "nullable": true|false,
          "unique": true|false,
          "default": "valor_default",
          "fillable": true|false,
          "sortable": true|false,
          "filterable": true|false
        }
      ]
    }
  },
  "relationships": [
    {
      "entityA": "EntityA",
      "entityB": "EntityB",
      "type": "one-to-many|many-to-one|many-to-many|one-to-one",
      "description": "Descripción de la relación"
    }
  ],
  "permissions": {
    "prefix": "module-prefix",
    "resources": ["entity-names"],
    "actions": ["index", "show", "store", "update", "destroy"],
    "roles": {
      "god": ["all"],
      "admin": ["all"],
      "tech": ["entity-names.index", "entity-names.show"]
    }
  },
  "seeding": {
    "entity_names": 25
  }
}
```

### Tipos de Campos Soportados

| Tipo | Descripción | Cast Automático | Ejemplo |
|------|-------------|-----------------|---------|
| `string` | Texto corto | `string` | `"name": "Juan"` |
| `text` | Texto largo | `string` | Descripciones |
| `integer` | Número entero | `integer` | `"quantity": 5` |
| `decimal` | Número decimal | `float` | `"price": 99.99` |
| `boolean` | Verdadero/Falso | `boolean` | `"is_active": true` |
| `date` | Solo fecha | `date` | `"birth_date": "1990-01-01"` |
| `datetime` | Fecha y hora | `datetime` | `"created_at": "2024-01-01 10:30:00"` |
| `json` | Objeto JSON | `array` | `{"key": "value"}` |
| `foreignId` | Clave foránea | `integer` | Relaciones |

### Tipos de Relaciones

| Tipo | Laravel Método | Descripción | Ejemplo |
|------|----------------|-------------|---------|
| `one-to-many` | `hasMany()` | 1 → N | Customer → Orders |
| `many-to-one` | `belongsTo()` | N → 1 | Order → Customer |
| `many-to-many` | `belongsToMany()` | N ↔ N | Products ↔ Categories |
| `one-to-one` | `hasOne()` | 1 → 1 | User → Profile |

---

## 4. Uso Básico

### Ejemplo 1: Módulo Simple (Blog)

#### Paso 1: Crear configuración

```bash
# Crear archivo: blog-config.json
```

```json
{
  "entities": {
    "Post": {
      "name": "Post",
      "tableName": "posts",
      "fields": [
        {
          "name": "title",
          "type": "string",
          "nullable": false,
          "fillable": true,
          "sortable": true,
          "filterable": true
        },
        {
          "name": "slug",
          "type": "string",
          "nullable": false,
          "unique": true,
          "fillable": true
        },
        {
          "name": "content",
          "type": "text",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "excerpt",
          "type": "text",
          "nullable": true,
          "fillable": true
        },
        {
          "name": "published_at",
          "type": "datetime",
          "nullable": true,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "is_published",
          "type": "boolean",
          "nullable": false,
          "default": false,
          "fillable": true,
          "filterable": true
        },
        {
          "name": "views_count",
          "type": "integer",
          "nullable": false,
          "default": 0,
          "sortable": true
        },
        {
          "name": "meta_data",
          "type": "json",
          "nullable": true,
          "fillable": true
        }
      ]
    }
  },
  "relationships": [],
  "permissions": {
    "prefix": "blog",
    "resources": ["posts"],
    "actions": ["index", "show", "store", "update", "destroy"],
    "roles": {
      "god": ["all"],
      "admin": ["all"],
      "tech": ["posts.index", "posts.show"]
    }
  },
  "seeding": {
    "posts": 50
  }
}
```

#### Paso 2: Generar módulo

```bash
php artisan module:advanced-blueprint Blog --config=blog-config.json
```

#### Paso 3: Migrar y poblar

```bash
php artisan migrate
php artisan db:seed --class="Modules\\Blog\\Database\\Seeders\\BlogDatabaseSeeder"
```

#### Paso 4: Probar

```bash
# Ver las rutas generadas
php artisan route:list | grep posts

# Ejecutar tests
php artisan test Modules/Blog/Tests/Feature/
```

**¡Resultado: Módulo completo funcionando en 30 segundos!** 🎉

---

## 5. Casos de Uso Avanzados

### Ejemplo 2: Sistema de Órdenes (Complejo)

```json
{
  "entities": {
    "Order": {
      "name": "Order",
      "tableName": "orders",
      "fields": [
        {
          "name": "order_number",
          "type": "string",
          "nullable": false,
          "unique": true,
          "fillable": true,
          "sortable": true,
          "filterable": true
        },
        {
          "name": "customer_id",
          "type": "foreignId",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "status",
          "type": "string",
          "nullable": false,
          "default": "pending",
          "fillable": true,
          "filterable": true
        },
        {
          "name": "order_date",
          "type": "date",
          "nullable": false,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "subtotal",
          "type": "decimal",
          "nullable": false,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "tax",
          "type": "decimal",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "total",
          "type": "decimal",
          "nullable": false,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "notes",
          "type": "text",
          "nullable": true,
          "fillable": true
        },
        {
          "name": "shipping_info",
          "type": "json",
          "nullable": true,
          "fillable": true
        }
      ]
    },
    "OrderItem": {
      "name": "OrderItem",
      "tableName": "order_items",
      "fields": [
        {
          "name": "order_id",
          "type": "foreignId",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "product_id",
          "type": "foreignId",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "quantity",
          "type": "integer",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "unit_price",
          "type": "decimal",
          "nullable": false,
          "fillable": true
        },
        {
          "name": "subtotal",
          "type": "decimal",
          "nullable": false,
          "fillable": true
        }
      ]
    }
  },
  "relationships": [
    {
      "entityA": "Order",
      "entityB": "OrderItem",
      "type": "one-to-many",
      "description": "Una orden puede tener múltiples items"
    },
    {
      "entityA": "OrderItem",
      "entityB": "Product",
      "type": "many-to-one",
      "description": "Cada item referencia un producto"
    },
    {
      "entityA": "Order",
      "entityB": "Customer",
      "type": "many-to-one",
      "description": "Cada orden pertenece a un cliente"
    }
  ],
  "permissions": {
    "prefix": "orders",
    "resources": ["orders", "order-items"],
    "actions": ["index", "show", "store", "update", "destroy"],
    "roles": {
      "god": ["all"],
      "admin": ["all"],
      "tech": ["orders.index", "orders.show", "order-items.index", "order-items.show"],
      "customer": ["orders.index", "orders.show"]
    }
  },
  "seeding": {
    "orders": 100,
    "order_items": 300
  }
}
```

**Generar:**
```bash
php artisan module:advanced-blueprint Orders --config=orders-config.json
```

### Ejemplo 3: E-commerce Completo

```json
{
  "entities": {
    "Product": {
      "name": "Product",
      "tableName": "products",
      "fields": [
        {
          "name": "name",
          "type": "string",
          "nullable": false,
          "fillable": true,
          "sortable": true,
          "filterable": true
        },
        {
          "name": "sku",
          "type": "string",
          "nullable": false,
          "unique": true,
          "fillable": true,
          "filterable": true
        },
        {
          "name": "price",
          "type": "decimal",
          "nullable": false,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "stock_quantity",
          "type": "integer",
          "nullable": false,
          "default": 0,
          "fillable": true,
          "sortable": true
        },
        {
          "name": "is_active",
          "type": "boolean",
          "nullable": false,
          "default": true,
          "fillable": true,
          "filterable": true
        }
      ]
    },
    "Category": {
      "name": "Category",
      "tableName": "categories",
      "fields": [
        {
          "name": "name",
          "type": "string",
          "nullable": false,
          "fillable": true,
          "sortable": true,
          "filterable": true
        },
        {
          "name": "slug",
          "type": "string",
          "nullable": false,
          "unique": true,
          "fillable": true
        },
        {
          "name": "description",
          "type": "text",
          "nullable": true,
          "fillable": true
        }
      ]
    }
  },
  "relationships": [
    {
      "entityA": "Product",
      "entityB": "Category",
      "type": "many-to-many",
      "description": "Los productos pueden pertenecer a múltiples categorías"
    }
  ],
  "permissions": {
    "prefix": "shop",
    "resources": ["products", "categories"],
    "actions": ["index", "show", "store", "update", "destroy"],
    "roles": {
      "god": ["all"],
      "admin": ["all"],
      "manager": ["products.*", "categories.index", "categories.show"],
      "customer": ["products.index", "products.show", "categories.index", "categories.show"]
    }
  },
  "seeding": {
    "categories": 20,
    "products": 200
  }
}
```

---

## 6. Eliminar Módulos

### Eliminación Segura y Completa

```bash
# Eliminar módulo completamente
php artisan module:force-delete ModuleName
```

**¿Qué elimina automáticamente?**

✅ **Directorio completo** del módulo  
✅ **Registros en Server.php** (schemas y authorizers)  
✅ **Entradas en DatabaseSeeder.php**  
✅ **Referencias en TestCase.php**  
✅ **Archivo modules_statuses.json**  
✅ **Cache de Composer** (autoload)  

### Eliminación Manual (Si necesitas más control)

```bash
# 1. Deshabilitar módulo
php artisan module:disable ModuleName

# 2. Rollback de migraciones
php artisan module:migrate-rollback ModuleName --force

# 3. Eliminar archivos
php artisan module:delete ModuleName

# 4. Limpiar registros manualmente
# - Editar app/JsonApi/V1/Server.php
# - Editar database/seeders/DatabaseSeeder.php
# - Ejecutar composer dump-autoload
```

### Verificar Eliminación

```bash
# Verificar que no aparezca en la lista
php artisan module:list

# Verificar que las rutas no existen
php artisan route:list | grep module-name

# Verificar que los tests no existen
php artisan test Modules/ModuleName/Tests/
```

---

## 7. Banderas y Opciones

### Comando Principal

```bash
php artisan module:advanced-blueprint {module} [opciones]
```

### Opciones Disponibles

| Bandera | Descripción | Ejemplo |
|---------|-------------|---------|
| `--config=archivo.json` | **Archivo de configuración JSON** | `--config=blog.json` |
| `--force` | **Sobrescribir módulo existente** | `--force` |
| `--help` | Mostrar ayuda del comando | `--help` |
| `--verbose` | Output detallado | `-vvv` |
| `--quiet` | Solo mostrar errores | `--quiet` |

### Ejemplos de Uso

```bash
# Modo básico
php artisan module:advanced-blueprint Blog --config=blog.json

# Sobrescribir módulo existente
php artisan module:advanced-blueprint Blog --config=blog.json --force

# Modo silencioso
php artisan module:advanced-blueprint Blog --config=blog.json --quiet

# Modo verbose (para debugging)
php artisan module:advanced-blueprint Blog --config=blog.json -vvv
```

### Creación Interactiva (Sin archivo JSON)

```bash
# Crear módulo de forma interactiva
php artisan module:advanced-blueprint Blog

# El comando te preguntará paso a paso:
# - Nombres de entidades
# - Campos de cada entidad
# - Tipos de relaciones
# - Configuración de permisos
```

---

## 8. Ejemplos Reales

### Configuraciones de Módulos Existentes

#### E-commerce (Completo)

```json
{
  "entities": {
    "ShoppingCart": {
      "name": "ShoppingCart",
      "tableName": "shopping_carts",
      "fields": [
        {"name": "session_id", "type": "string", "nullable": true},
        {"name": "user_id", "type": "foreignId", "nullable": true},
        {"name": "status", "type": "string", "nullable": false},
        {"name": "total_amount", "type": "decimal", "nullable": false},
        {"name": "currency", "type": "string", "nullable": false},
        {"name": "discount_amount", "type": "decimal", "nullable": false},
        {"name": "tax_amount", "type": "decimal", "nullable": false}
      ]
    },
    "CartItem": {
      "name": "CartItem",
      "tableName": "cart_items",
      "fields": [
        {"name": "shopping_cart_id", "type": "foreignId", "nullable": false},
        {"name": "product_id", "type": "foreignId", "nullable": false},
        {"name": "quantity", "type": "decimal", "nullable": false},
        {"name": "unit_price", "type": "decimal", "nullable": false},
        {"name": "subtotal", "type": "decimal", "nullable": false}
      ]
    }
  },
  "relationships": [
    {
      "entityA": "ShoppingCart",
      "entityB": "CartItem", 
      "type": "one-to-many"
    }
  ]
}
```

#### CRM Simple

```json
{
  "entities": {
    "Contact": {
      "name": "Contact",
      "tableName": "contacts",
      "fields": [
        {"name": "first_name", "type": "string", "nullable": false, "fillable": true},
        {"name": "last_name", "type": "string", "nullable": false, "fillable": true},
        {"name": "email", "type": "string", "nullable": false, "unique": true, "fillable": true},
        {"name": "phone", "type": "string", "nullable": true, "fillable": true},
        {"name": "company", "type": "string", "nullable": true, "fillable": true},
        {"name": "position", "type": "string", "nullable": true, "fillable": true},
        {"name": "notes", "type": "text", "nullable": true, "fillable": true},
        {"name": "tags", "type": "json", "nullable": true, "fillable": true},
        {"name": "is_active", "type": "boolean", "default": true, "fillable": true}
      ]
    }
  }
}
```

#### Sistema de Tareas

```json
{
  "entities": {
    "Project": {
      "name": "Project",
      "tableName": "projects",
      "fields": [
        {"name": "name", "type": "string", "nullable": false, "fillable": true},
        {"name": "description", "type": "text", "nullable": true, "fillable": true},
        {"name": "start_date", "type": "date", "nullable": true, "fillable": true},
        {"name": "end_date", "type": "date", "nullable": true, "fillable": true},
        {"name": "status", "type": "string", "default": "active", "fillable": true}
      ]
    },
    "Task": {
      "name": "Task",
      "tableName": "tasks",
      "fields": [
        {"name": "project_id", "type": "foreignId", "nullable": false},
        {"name": "title", "type": "string", "nullable": false, "fillable": true},
        {"name": "description", "type": "text", "nullable": true, "fillable": true},
        {"name": "priority", "type": "string", "default": "medium", "fillable": true},
        {"name": "status", "type": "string", "default": "pending", "fillable": true},
        {"name": "due_date", "type": "datetime", "nullable": true, "fillable": true},
        {"name": "completed_at", "type": "datetime", "nullable": true}
      ]
    }
  },
  "relationships": [
    {
      "entityA": "Project",
      "entityB": "Task",
      "type": "one-to-many"
    }
  ]
}
```

---

## 9. Troubleshooting

### Error: "Module already exists"

**Problema:** El módulo ya existe y no usaste `--force`

**Solución:**
```bash
# Opción 1: Usar force
php artisan module:advanced-blueprint Blog --config=blog.json --force

# Opción 2: Eliminar primero
php artisan module:force-delete Blog
php artisan module:advanced-blueprint Blog --config=blog.json
```

### Error: "Configuration file not found"

**Problema:** El archivo JSON no existe o la ruta es incorrecta

**Solución:**
```bash
# Verificar que el archivo existe
ls -la config.json

# Usar ruta absoluta
php artisan module:advanced-blueprint Blog --config=/full/path/to/config.json

# O usar ruta relativa desde la raíz del proyecto
php artisan module:advanced-blueprint Blog --config=examples/blog.json
```

### Error: "Invalid JSON configuration"

**Problema:** El JSON tiene errores de sintaxis

**Solución:**
```bash
# Validar JSON online: https://jsonlint.com/

# O usar comando local
cat config.json | python -m json.tool

# Errores comunes:
# - Comas al final: {"name": "test",} ❌
# - Comillas simples: {'name': 'test'} ❌
# - Sin comillas en keys: {name: "test"} ❌
```

### Error: "Entity name conflicts"

**Problema:** El nombre de entidad ya existe en otro módulo

**Solución:**
```bash
# Ver módulos existentes
php artisan module:list

# Usar nombres únicos
# En lugar de "User", usar "AppUser" o "SystemUser"
```

### Error: "Migration fails"

**Problema:** Problema en migración generada

**Solución:**
```bash
# Ver el error específico
php artisan migrate --verbose

# Rollback si es necesario
php artisan migrate:rollback

# Editar migración manualmente si es necesario
# Luego ejecutar
php artisan migrate
```

### Error: "Tests fail"

**Problema:** Tests generados fallan

**Solución:**
```bash
# Ejecutar migración fresh
php artisan migrate:fresh --seed

# Ejecutar tests con detalles
php artisan test Modules/ModuleName/Tests/ --verbose

# Si fallan permisos, verificar seeders
php artisan db:seed --class="RolePermissionSeeder"
```

### Error: "Routes not found"

**Problema:** Las rutas no aparecen

**Solución:**
```bash
# Limpiar cache
php artisan optimize:clear

# Verificar registro en Server.php
# Verificar RouteServiceProvider del módulo

# Verificar rutas
php artisan route:list | grep module-name
```

### Error: "Class not found"

**Problema:** Autoload no actualizado

**Solución:**
```bash
# Actualizar autoload
composer dump-autoload

# Limpiar cache
php artisan optimize:clear

# Verificar namespace en composer.json
```

---

## 10. Tips y Mejores Prácticas

### 🎯 Naming Conventions

```json
// ✅ CORRECTO
{
  "entities": {
    "BlogPost": {           // PascalCase singular
      "tableName": "blog_posts",  // snake_case plural
      "fields": [
        {
          "name": "title",        // snake_case
          "type": "string"
        }
      ]
    }
  }
}

// ❌ INCORRECTO  
{
  "entities": {
    "blog_posts": {         // No usar snake_case
      "tableName": "BlogPost",   // No usar PascalCase
      "fields": [
        {
          "name": "Title",        // No usar PascalCase
          "type": "String"        // No capitalizar types
        }
      ]
    }
  }
}
```

### 🔗 Relaciones Efectivas

```json
// ✅ BUENA PRÁCTICA: Relaciones claras
{
  "relationships": [
    {
      "entityA": "Customer",
      "entityB": "Order", 
      "type": "one-to-many",
      "description": "Un cliente puede tener múltiples órdenes"
    }
  ]
}

// 💡 TIP: Siempre agregar descripción para claridad
```

### 🔐 Permisos Granulares

```json
{
  "permissions": {
    "roles": {
      "admin": ["all"],
      "manager": [
        "orders.index", "orders.show", "orders.update",
        "customers.index", "customers.show"
      ],
      "employee": ["orders.index", "orders.show"],
      "customer": ["orders.show"]  // Solo sus propias órdenes
    }
  }
}
```

### 📊 Seeding Realista

```json
{
  "seeding": {
    "customers": 50,      // Pocos clientes
    "orders": 200,        // Muchas órdenes por cliente
    "order_items": 500    // Muchos items por orden
  }
}
```

### 🧪 Testing Strategy

```bash
# Ejecutar tests durante desarrollo
php artisan test Modules/NewModule/Tests/ --stop-on-failure

# Verificar cobertura
php artisan test --coverage --min=80

# Tests específicos
php artisan test --filter=test_admin_can_create
```

### ⚡ Performance

```json
{
  "fields": [
    {
      "name": "email",
      "type": "string",
      "unique": true,      // ✅ Índice automático
      "filterable": true   // ✅ Búsquedas optimizadas
    },
    {
      "name": "status", 
      "type": "string",
      "filterable": true   // ✅ Para filtros comunes
    }
  ]
}
```

### 🔄 Versionado

```bash
# Siempre versionar configuraciones
git add blog-v1.json
git commit -m "Initial blog module configuration"

# Hacer backup antes de regenerar
cp blog.json blog-backup-$(date +%Y%m%d).json
php artisan module:advanced-blueprint Blog --config=blog.json --force
```

### 🎛️ Desarrollo Iterativo

```bash
# 1. Crear versión básica
php artisan module:advanced-blueprint Blog --config=blog-basic.json

# 2. Probar funcionalidad básica
php artisan test Modules/Blog/Tests/

# 3. Agregar campos/relaciones
# Editar blog-advanced.json

# 4. Regenerar con --force
php artisan module:advanced-blueprint Blog --config=blog-advanced.json --force
```

### 📝 Documentación Automática

```bash
# Generar documentación después de crear módulo
php artisan api:generate-docs

# Crear README del módulo
php artisan module:docs BlogModule
```

---

## 📚 Comandos de Referencia Rápida

### Flujo Completo

```bash
# 1. Crear configuración JSON
nano blog-config.json

# 2. Generar módulo
php artisan module:advanced-blueprint Blog --config=blog-config.json

# 3. Migrar
php artisan migrate

# 4. Poblar datos
php artisan db:seed --class="Modules\\Blog\\Database\\Seeders\\BlogDatabaseSeeder"

# 5. Probar
php artisan test Modules/Blog/Tests/Feature/

# 6. Ver rutas
php artisan route:list | grep posts

# 7. Generar documentación
php artisan api:generate-docs
```

### Comandos de Debugging

```bash
# Ver estructura del módulo
tree Modules/Blog/

# Verificar configuración
php artisan module:list

# Ver migraciones
php artisan migrate:status

# Verificar permisos
php artisan tinker
>>> Role::with('permissions')->get()

# Ver logs
tail -f storage/logs/laravel.log
```

---

**¡Con esta guía puedes crear módulos complejos en minutos!** 🚀

El Advanced Blueprint Generator te ahorra **horas de trabajo manual** y garantiza **consistencia** en toda tu aplicación.

**¿Dudas?** Revisa primero el [Troubleshooting](#9-troubleshooting) o los [ejemplos reales](#8-ejemplos-reales).