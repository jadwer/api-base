# JSON:API Relationships Guide

Este documento explica cómo usar relaciones en nuestra API que sigue el estándar JSON:API 1.1.

## 🔗 Entendiendo las Relaciones JSON:API

En JSON:API, las relaciones se manejan de manera separada a los atributos. Esto permite un control granular sobre qué datos incluir en cada request.

### Estructura Básica

```javascript
{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      // Campos directos del recurso
      "title": "Mi página",
      "status": "published"
    },
    "relationships": {
      // Referencias a otros recursos
      "user": {
        "data": {
          "type": "users",
          "id": "5"
        }
      }
    }
  }
}
```

## 📝 Creando Recursos con Relaciones

### Ejemplo: Crear una Página con Usuario

```javascript
POST /api/v1/pages
Content-Type: application/vnd.api+json
Authorization: Bearer your-token

{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "Nueva página",
      "slug": "nueva-pagina",
      "html": "<h1>Contenido HTML</h1>",
      "css": "h1 { color: blue; }",
      "json": {
        "component": "header",
        "settings": { "background": "#fff" }
      },
      "status": "draft"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "3"
        }
      }
    }
  }
}
```

### Ejemplo: Crear Producto con Categoría

```javascript
POST /api/v1/products
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "products",
    "attributes": {
      "name": "Laptop Gaming",
      "price": 1500.00,
      "description": "Laptop para gaming"
    },
    "relationships": {
      "category": {
        "data": {
          "type": "categories",
          "id": "2"
        }
      },
      "brand": {
        "data": {
          "type": "brands", 
          "id": "1"
        }
      }
    }
  }
}
```

## 🔄 Actualizando Relaciones

### Cambiar la relación de un recurso

```javascript
PATCH /api/v1/pages/1
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "status": "published"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "7"
        }
      }
    }
  }
}
```

### Solo actualizar atributos (sin tocar relaciones)

```javascript
PATCH /api/v1/pages/1
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "title": "Título actualizado",
      "status": "published"
    }
  }
}
```

## 📖 Consultando con Relaciones Incluidas

### Incluir datos de la relación en la respuesta

```javascript
GET /api/v1/pages/1?include=user
Accept: application/vnd.api+json

// Respuesta incluye los datos del usuario
{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "title": "Mi página",
      "status": "published"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "3"
        }
      }
    }
  },
  "included": [
    {
      "type": "users",
      "id": "3",
      "attributes": {
        "name": "Juan Pérez",
        "email": "juan@example.com"
      }
    }
  ]
}
```

### Incluir múltiples relaciones

```javascript
GET /api/v1/products/1?include=category,brand,unit
```

## 🚫 Errores Comunes

### ❌ Incorrecto - Enviar ID como atributo
```javascript
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "Test",
      "userId": 1  // ❌ No es un atributo, es una relación
    }
  }
}
```

### ✅ Correcto - Enviar como relación
```javascript
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "Test"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "1"  // ✅ Siempre como string
        }
      }
    }
  }
}
```

### ❌ Incorrecto - ID como número
```javascript
{
  "relationships": {
    "user": {
      "data": {
        "type": "users",
        "id": 1  // ❌ Los IDs deben ser strings en JSON:API
      }
    }
  }
}
```

### ✅ Correcto - ID como string
```javascript
{
  "relationships": {
    "user": {
      "data": {
        "type": "users",
        "id": "1"  // ✅ Siempre string
      }
    }
  }
}
```

## 🔍 Filtrar por Relaciones

### Filtrar páginas por usuario específico

```javascript
GET /api/v1/pages?filter[user]=3
```

### Combinar filtros
```javascript
GET /api/v1/pages?filter[status]=published&filter[user]=3
```

## 📚 Recursos por Módulo

### PageBuilder
- **pages** → **user**: `BelongsTo`
  ```javascript
  "relationships": {
    "user": { "data": { "type": "users", "id": "1" } }
  }
  ```

### Product
- **products** → **category**: `BelongsTo`
- **products** → **brand**: `BelongsTo` 
- **products** → **unit**: `BelongsTo`

### Inventory
- **warehouse_locations** → **warehouse**: `BelongsTo`
- **stock** → **product**: `BelongsTo`
- **stock** → **warehouse_location**: `BelongsTo`

### Purchase
- **purchase_orders** → **supplier**: `BelongsTo`
- **purchase_order_items** → **purchase_order**: `BelongsTo`
- **purchase_order_items** → **product**: `BelongsTo`

### Sales  
- **sales_orders** → **customer**: `BelongsTo`
- **sales_order_items** → **sales_order**: `BelongsTo`
- **sales_order_items** → **product**: `BelongsTo`

### Ecommerce
- **shopping_carts** → **user**: `BelongsTo`
- **cart_items** → **shopping_cart**: `BelongsTo`
- **cart_items** → **product**: `BelongsTo`

## ⚡ Tips y Mejores Prácticas

1. **Siempre usa strings para IDs** en JSON:API
2. **Valida que el recurso existe** antes de enviar la relación
3. **Usa `include`** para reducir requests y obtener datos relacionados
4. **Los filtros por relación** son útiles para obtener recursos específicos
5. **Puedes omitir `relationships`** si no necesitas cambiar ninguna relación

## 🔧 Debugging

### Ver la estructura completa de un recurso
```bash
curl -H "Accept: application/vnd.api+json" \
     -H "Authorization: Bearer your-token" \
     http://localhost/api/v1/pages/1?include=user | jq .
```

### Validar que una relación existe
```bash
curl -H "Accept: application/vnd.api+json" \
     -H "Authorization: Bearer your-token" \
     http://localhost/api/v1/users/1
```