# 📋 API Examples - Requests y Responses Reales

**Ejemplos completos con requests y responses reales de la API**

---

## 🔐 Autenticación

### Login Admin

**Request:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**Response 200:**
```json
{
  "user": {
    "id": 1,
    "name": "God Admin",
    "email": "admin@example.com",
    "email_verified_at": "2024-01-15T10:00:00.000000Z",
    "is_active": true,
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z"
  },
  "token": "1|kZkxjEn3NcKNwCnb2ePqSGPtJhUVRhGYkBqnHkpL73f8a2d1b9e6c4a7f3d8e5b2"
}
```

### Login Error

**Request:**
```json
{
  "email": "wrong@example.com",
  "password": "wrong"
}
```

**Response 401:**
```json
{
  "message": "These credentials do not match our records."
}
```

---

## 📦 Productos (Products)

### GET /api/v1/products - Listar Productos

**Request:**
```bash
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer 1|kZkxjEn3NcKNwCnb2ePqSGPtJhUVRhGYkBqnHkpL73f8a2d1" \
  -H "Content-Type: application/vnd.api+json"
```

**Response 200:**
```json
{
  "data": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "name": "Laptop Dell XPS 13",
        "sku": "LAP-DELL-001",
        "description": "Ultrabook profesional con pantalla 13.3\" y procesador Intel i7",
        "fullDescription": "Laptop ultraligera perfecta para profesionales que necesitan rendimiento y portabilidad. Pantalla táctil 4K, 16GB RAM, SSD 512GB.",
        "price": 1299.99,
        "cost": 950.00,
        "iva": true,
        "imgPath": "/images/products/laptop-dell-xps13.jpg",
        "datasheetPath": "/datasheets/dell-xps13-specs.pdf",
        "createdAt": "2024-01-15T10:30:00.000000Z",
        "updatedAt": "2024-01-15T10:30:00.000000Z"
      },
      "relationships": {
        "category": {
          "data": {
            "type": "categories",
            "id": "1"
          }
        },
        "brand": {
          "data": {
            "type": "brands", 
            "id": "2"
          }
        },
        "unit": {
          "data": {
            "type": "units",
            "id": "1"
          }
        }
      },
      "links": {
        "self": "http://localhost:8000/api/v1/products/1"
      }
    },
    {
      "type": "products",
      "id": "2",
      "attributes": {
        "name": "iPhone 15 Pro",
        "sku": "IPH-15-PRO",
        "description": "El iPhone más avanzado",
        "fullDescription": "iPhone 15 Pro con chip A17 Pro, cámara de 48MP y pantalla Super Retina XDR de 6.1 pulgadas.",
        "price": 1199.00,
        "cost": 800.00,
        "iva": true,
        "imgPath": "/images/products/iphone-15-pro.jpg",
        "datasheetPath": null,
        "createdAt": "2024-01-15T11:00:00.000000Z",
        "updatedAt": "2024-01-15T11:00:00.000000Z"
      },
      "relationships": {
        "category": {
          "data": {
            "type": "categories",
            "id": "1"
          }
        },
        "brand": {
          "data": {
            "type": "brands",
            "id": "3"
          }
        },
        "unit": {
          "data": {
            "type": "units",
            "id": "1"
          }
        }
      },
      "links": {
        "self": "http://localhost:8000/api/v1/products/2"
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/products?page%5Bnumber%5D=1",
    "last": "http://localhost:8000/api/v1/products?page%5Bnumber%5D=3",
    "prev": null,
    "next": "http://localhost:8000/api/v1/products?page%5Bnumber%5D=2"
  },
  "meta": {
    "page": {
      "currentPage": 1,
      "from": 1,
      "lastPage": 3,
      "perPage": 15,
      "to": 15,
      "total": 45
    }
  }
}
```

### GET /api/v1/products?include=category,brand - Con Relaciones

**Response 200:**
```json
{
  "data": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "name": "Laptop Dell XPS 13",
        "sku": "LAP-DELL-001",
        "price": 1299.99
      },
      "relationships": {
        "category": {
          "data": {
            "type": "categories",
            "id": "1"
          }
        },
        "brand": {
          "data": {
            "type": "brands",
            "id": "2"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "categories",
      "id": "1",
      "attributes": {
        "name": "Electrónicos",
        "description": "Productos electrónicos y tecnológicos",
        "isActive": true,
        "createdAt": "2024-01-15T10:00:00.000000Z",
        "updatedAt": "2024-01-15T10:00:00.000000Z"
      }
    },
    {
      "type": "brands",
      "id": "2",
      "attributes": {
        "name": "Dell",
        "description": "Tecnología Dell",
        "logoPath": "/images/brands/dell-logo.png",
        "isActive": true,
        "createdAt": "2024-01-15T10:00:00.000000Z",
        "updatedAt": "2024-01-15T10:00:00.000000Z"
      }
    }
  ]
}
```

### POST /api/v1/products - Crear Producto

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer 1|kZkxjEn3NcKNwCnb2ePqSGPtJhUVRhGYkBqnHkpL73f8a2d1" \
  -H "Content-Type: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "products",
      "attributes": {
        "name": "MacBook Air M2",
        "sku": "MBA-M2-001",
        "description": "MacBook Air con chip M2",
        "fullDescription": "El MacBook Air más delgado y ligero con el revolucionario chip M2 de Apple.",
        "price": 1399.99,
        "cost": 1000.00,
        "iva": true,
        "imgPath": "/images/products/macbook-air-m2.jpg"
      },
      "relationships": {
        "category": {
          "data": { "type": "categories", "id": "1" }
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

**Response 201:**
```json
{
  "data": {
    "type": "products",
    "id": "46",
    "attributes": {
      "name": "MacBook Air M2",
      "sku": "MBA-M2-001",
      "description": "MacBook Air con chip M2",
      "fullDescription": "El MacBook Air más delgado y ligero con el revolucionario chip M2 de Apple.",
      "price": 1399.99,
      "cost": 1000.00,
      "iva": true,
      "imgPath": "/images/products/macbook-air-m2.jpg",
      "datasheetPath": null,
      "createdAt": "2024-01-15T15:30:25.000000Z",
      "updatedAt": "2024-01-15T15:30:25.000000Z"
    },
    "relationships": {
      "category": {
        "data": {
          "type": "categories",
          "id": "1"
        }
      },
      "brand": {
        "data": {
          "type": "brands",
          "id": "3"
        }
      },
      "unit": {
        "data": {
          "type": "units",
          "id": "1"
        }
      }
    },
    "links": {
      "self": "http://localhost:8000/api/v1/products/46"
    }
  }
}
```

### Error 422 - Validación Falla

**Request:** (falta name requerido)
```json
{
  "data": {
    "type": "products",
    "attributes": {
      "sku": "TEST-001",
      "price": 100.00
    }
  }
}
```

**Response 422:**
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "El nombre es obligatorio.",
      "source": {
        "pointer": "/data/attributes/name"
      }
    }
  ]
}
```

---

## 🏢 Clientes (Customers)

### GET /api/v1/customers - Listar Clientes

**Response 200:**
```json
{
  "data": [
    {
      "type": "customers",
      "id": "1",
      "attributes": {
        "name": "Acme Corporation",
        "email": "contact@acme.com",
        "phone": "+1-555-0199",
        "address": "123 Business Ave, Suite 100",
        "classification": "premium",
        "creditLimit": 50000.00,
        "currentBalance": 12500.75,
        "isActive": true,
        "createdAt": "2024-01-15T09:00:00.000000Z",
        "updatedAt": "2024-01-15T14:22:10.000000Z"
      },
      "relationships": {
        "salesOrders": {
          "data": [
            {
              "type": "sales-orders",
              "id": "1"
            },
            {
              "type": "sales-orders", 
              "id": "3"
            }
          ]
        }
      },
      "links": {
        "self": "http://localhost:8000/api/v1/customers/1"
      }
    }
  ]
}
```

### GET /api/v1/customers?filter[classification]=premium&sort=-creditLimit

**Response 200:**
```json
{
  "data": [
    {
      "type": "customers",
      "id": "1",
      "attributes": {
        "name": "Acme Corporation",
        "classification": "premium",
        "creditLimit": 50000.00
      }
    },
    {
      "type": "customers",
      "id": "5",
      "attributes": {
        "name": "Tech Solutions Inc",
        "classification": "premium", 
        "creditLimit": 35000.00
      }
    }
  ]
}
```

---

## 📦 Inventario (Inventory)

### POST /api/v1/inventory-movements - Movimiento de Inventario

**Request:**
```json
{
  "data": {
    "type": "inventory-movements",
    "attributes": {
      "movementType": "purchase",
      "quantity": 50,
      "unitCost": 950.00,
      "totalCost": 47500.00,
      "notes": "Compra directa del proveedor - Orden PO-2024-001",
      "previousStock": 25,
      "newStock": 75,
      "batchInfo": {
        "lotNumber": "LOT-2024-001",
        "expirationDate": "2025-12-31",
        "supplier": "Dell Technologies"
      },
      "metadata": {
        "purchaseOrderId": "PO-2024-001",
        "receiptNumber": "RCP-001",
        "qualityCheck": "passed"
      }
    },
    "relationships": {
      "product": {
        "data": { "type": "products", "id": "1" }
      },
      "warehouse": {
        "data": { "type": "warehouses", "id": "1" }
      },
      "productBatch": {
        "data": { "type": "product-batches", "id": "3" }
      }
    }
  }
}
```

**Response 201:**
```json
{
  "data": {
    "type": "inventory-movements",
    "id": "15",
    "attributes": {
      "movementType": "purchase",
      "quantity": 50,
      "unitCost": 950.00,
      "totalCost": 47500.00,
      "notes": "Compra directa del proveedor - Orden PO-2024-001",
      "previousStock": 25,
      "newStock": 75,
      "batchInfo": {
        "lotNumber": "LOT-2024-001",
        "expirationDate": "2025-12-31",
        "supplier": "Dell Technologies"
      },
      "metadata": {
        "purchaseOrderId": "PO-2024-001",
        "receiptNumber": "RCP-001",
        "qualityCheck": "passed"
      },
      "createdAt": "2024-01-15T16:45:30.000000Z",
      "updatedAt": "2024-01-15T16:45:30.000000Z"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "1"
        }
      },
      "productBatch": {
        "data": {
          "type": "product-batches",
          "id": "3"
        }
      }
    }
  }
}
```

---

## 🛒 E-commerce (Shopping Carts)

### POST /api/v1/shopping-carts - Crear Carrito

**Request:**
```json
{
  "data": {
    "type": "shopping-carts",
    "attributes": {
      "sessionId": "session_abc123def456",
      "subtotal": 0.00,
      "tax": 0.00,
      "discount": 0.00,
      "total": 0.00,
      "status": "active"
    },
    "relationships": {
      "user": {
        "data": { "type": "users", "id": "2" }
      }
    }
  }
}
```

**Response 201:**
```json
{
  "data": {
    "type": "shopping-carts",
    "id": "8",
    "attributes": {
      "sessionId": "session_abc123def456",
      "subtotal": 0.00,
      "tax": 0.00,
      "discount": 0.00,
      "total": 0.00,
      "status": "active",
      "createdAt": "2024-01-15T17:20:15.000000Z",
      "updatedAt": "2024-01-15T17:20:15.000000Z"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "2"
        }
      },
      "coupon": {
        "data": null
      }
    }
  }
}
```

### POST /api/v1/cart-items - Agregar Item al Carrito

**Request:**
```json
{
  "data": {
    "type": "cart-items",
    "attributes": {
      "quantity": 2,
      "price": 1299.99,
      "subtotal": 2599.98
    },
    "relationships": {
      "shoppingCart": {
        "data": { "type": "shopping-carts", "id": "8" }
      },
      "product": {
        "data": { "type": "products", "id": "1" }
      }
    }
  }
}
```

**Response 201:**
```json
{
  "data": {
    "type": "cart-items",
    "id": "12",
    "attributes": {
      "quantity": 2,
      "price": 1299.99,
      "subtotal": 2599.98,
      "createdAt": "2024-01-15T17:25:30.000000Z",
      "updatedAt": "2024-01-15T17:25:30.000000Z"
    },
    "relationships": {
      "shoppingCart": {
        "data": {
          "type": "shopping-carts",
          "id": "8"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      }
    }
  }
}
```

---

## 💰 Ventas (Sales Orders)

### GET /api/v1/sales-orders?include=customer,salesOrderItems.product

**Response 200:**
```json
{
  "data": [
    {
      "type": "sales-orders",
      "id": "1",
      "attributes": {
        "orderNumber": "SO-2024-001",
        "orderDate": "2024-01-15",
        "status": "confirmed",
        "subtotal": 2599.98,
        "tax": 467.996,
        "discount": 0.00,
        "total": 3067.976,
        "notes": "Orden prioritaria - Cliente premium",
        "createdAt": "2024-01-15T12:30:00.000000Z",
        "updatedAt": "2024-01-15T13:45:22.000000Z"
      },
      "relationships": {
        "customer": {
          "data": {
            "type": "customers",
            "id": "1"
          }
        },
        "salesOrderItems": {
          "data": [
            {
              "type": "sales-order-items",
              "id": "1"
            },
            {
              "type": "sales-order-items",
              "id": "2"
            }
          ]
        }
      }
    }
  ],
  "included": [
    {
      "type": "customers",
      "id": "1",
      "attributes": {
        "name": "Acme Corporation",
        "email": "contact@acme.com",
        "classification": "premium"
      }
    },
    {
      "type": "sales-order-items",
      "id": "1",
      "attributes": {
        "quantity": 2,
        "unitPrice": 1299.99,
        "subtotal": 2599.98
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "name": "Laptop Dell XPS 13",
        "sku": "LAP-DELL-001",
        "price": 1299.99
      }
    }
  ]
}
```

---

## 🚫 Errores Comunes

### Error 401 - No Autenticado

**Response:**
```json
{
  "message": "Unauthenticated."
}
```

### Error 403 - Sin Permisos

**Response:**
```json
{
  "message": "This action is unauthorized."
}
```

### Error 404 - Recurso No Encontrado

**Response:**
```json
{
  "errors": [
    {
      "status": "404",
      "title": "Not Found",
      "detail": "The resource does not exist."
    }
  ]
}
```

### Error 422 - Validación de Relaciones

**Request:** (referencia a categoria inexistente)
```json
{
  "data": {
    "type": "products",
    "attributes": {
      "name": "Test Product"
    },
    "relationships": {
      "category": {
        "data": { "type": "categories", "id": "999" }
      }
    }
  }
}
```

**Response 422:**
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The selected category is invalid.",
      "source": {
        "pointer": "/data/relationships/category"
      }
    }
  ]
}
```

---

## 📊 Filtros y Búsquedas Avanzadas

### Filtros Múltiples
```bash
GET /api/v1/products?filter[name]=laptop&filter[price]=>1000&filter[iva]=true&sort=-price
```

### Filtros por ID
```bash
GET /api/v1/products?filter[id]=1,5,10,15
```

### Búsqueda por Relación
```bash
GET /api/v1/products?filter[category]=1&include=category
```

### Paginación Personalizada
```bash
GET /api/v1/products?page[number]=3&page[size]=5&sort=name
```

---

**Nota:** Todos estos ejemplos están basados en responses reales del sistema api-base con JSON:API 5.x compliance.