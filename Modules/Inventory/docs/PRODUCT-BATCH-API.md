# 📋 **Estructura de Endpoints ProductBatch**

## **Headers Requeridos**
```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

---

## **1. GET `/api/v1/product-batches` - Listar Batches**

### **Request**
```bash
GET /api/v1/product-batches
```

### **Query Parameters Opcionales**
```bash
# Filtros
?filter[status]=active
?filter[batch_number]=BATCH-2025
?filter[product_id]=1
?filter[warehouse_id]=1

# Sorting
?sort=expirationDate
?sort=-createdAt
?sort=batchNumber,expirationDate

# Relationships
?include=product,warehouse,warehouseLocation

# Paginación
?page[number]=1&page[size]=20
```

### **Response**
```json
{
  "data": [
    {
      "type": "product-batches",
      "id": "1",
      "attributes": {
        "batchNumber": "BATCH-202508-001-01",
        "lotNumber": "LOT123456",
        "manufacturingDate": "2024-06-15T00:00:00.000000Z",
        "expirationDate": "2026-06-15T00:00:00.000000Z",
        "bestBeforeDate": "2026-03-15T00:00:00.000000Z",
        "initialQuantity": 1000.0000,
        "currentQuantity": 850.0000,
        "reservedQuantity": 50.0000,
        "availableQuantity": 800.0000,
        "unitCost": 25.5000,
        "totalValue": 21675.0000,
        "status": "active",
        "supplierName": "Proveedor ABC S.A.",
        "supplierBatch": "SUP12345678",
        "qualityNotes": "Producto cumple con especificaciones",
        "testResults": {
          "pH": 7.2,
          "moisture": "2.5%",
          "purity": "99.8%"
        },
        "certifications": {
          "ISO9001": true,
          "HACCP": true,
          "Organic": false
        },
        "metadata": {
          "temperature_storage": "-18°C",
          "handling_instructions": "Mantener congelado"
        },
        "createdAt": "2025-08-18T10:30:00.000000Z",
        "updatedAt": "2025-08-18T11:15:00.000000Z"
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
        "warehouseLocation": {
          "data": {
            "type": "warehouse-locations",
            "id": "1"
          }
        }
      }
    }
  ],
  "meta": {
    "page": {
      "currentPage": 1,
      "from": 1,
      "lastPage": 3,
      "perPage": 20,
      "to": 20,
      "total": 52
    }
  }
}
```

---

## **2. GET `/api/v1/product-batches/{id}` - Ver Batch Específico**

### **Request**
```bash
GET /api/v1/product-batches/1?include=product,warehouse
```

### **Response**
```json
{
  "data": {
    "type": "product-batches",
    "id": "1",
    "attributes": {
      "batchNumber": "BATCH-202508-001-01",
      "lotNumber": "LOT123456",
      "manufacturingDate": "2024-06-15T00:00:00.000000Z",
      "expirationDate": "2026-06-15T00:00:00.000000Z",
      "bestBeforeDate": "2026-03-15T00:00:00.000000Z",
      "initialQuantity": 1000.0000,
      "currentQuantity": 850.0000,
      "reservedQuantity": 50.0000,
      "availableQuantity": 800.0000,
      "unitCost": 25.5000,
      "totalValue": 21675.0000,
      "status": "active",
      "supplierName": "Proveedor ABC S.A.",
      "supplierBatch": "SUP12345678",
      "qualityNotes": "Producto cumple con especificaciones",
      "testResults": {
        "pH": 7.2,
        "moisture": "2.5%"
      },
      "certifications": {
        "ISO9001": true,
        "HACCP": true
      },
      "metadata": {
        "temperature_storage": "-18°C"
      },
      "createdAt": "2025-08-18T10:30:00.000000Z",
      "updatedAt": "2025-08-18T11:15:00.000000Z"
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
      "warehouseLocation": {
        "data": {
          "type": "warehouse-locations",
          "id": "1"
        }
      }
    }
  },
  "included": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "name": "Producto Ejemplo",
        "sku": "PROD-001"
      }
    },
    {
      "type": "warehouses",
      "id": "1", 
      "attributes": {
        "name": "Almacén Principal",
        "code": "ALM-001"
      }
    }
  ]
}
```

---

## **3. POST `/api/v1/product-batches` - Crear Nuevo Batch**

### **Request Body**
```json
{
  "data": {
    "type": "product-batches",
    "attributes": {
      "batchNumber": "BATCH-202508-002-01",
      "lotNumber": "LOT789012",
      "manufacturingDate": "2024-08-15",
      "expirationDate": "2026-08-15",
      "bestBeforeDate": "2026-05-15",
      "initialQuantity": 500.0000,
      "currentQuantity": 500.0000,
      "reservedQuantity": 0.0000,
      "unitCost": 30.0000,
      "status": "active",
      "supplierName": "Nuevo Proveedor S.A.",
      "supplierBatch": "SUP87654321",
      "qualityNotes": "Lote nuevo, calidad excelente",
      "testResults": {
        "pH": 7.0,
        "moisture": "2.0%",
        "purity": "99.9%"
      },
      "certifications": {
        "ISO9001": true,
        "HACCP": true,
        "Organic": true
      },
      "metadata": {
        "temperature_storage": "-20°C",
        "origin_country": "México"
      }
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
      "warehouseLocation": {
        "data": {
          "type": "warehouse-locations",
          "id": "2"
        }
      }
    }
  }
}
```

### **Response (201 Created)**
```json
{
  "data": {
    "type": "product-batches",
    "id": "42",
    "attributes": {
      "batchNumber": "BATCH-202508-002-01",
      "lotNumber": "LOT789012",
      "manufacturingDate": "2024-08-15T00:00:00.000000Z",
      "expirationDate": "2026-08-15T00:00:00.000000Z",
      "bestBeforeDate": "2026-05-15T00:00:00.000000Z",
      "initialQuantity": 500.0000,
      "currentQuantity": 500.0000,
      "reservedQuantity": 0.0000,
      "availableQuantity": 500.0000,
      "unitCost": 30.0000,
      "totalValue": 15000.0000,
      "status": "active",
      "supplierName": "Nuevo Proveedor S.A.",
      "supplierBatch": "SUP87654321",
      "qualityNotes": "Lote nuevo, calidad excelente",
      "testResults": {
        "pH": 7.0,
        "moisture": "2.0%",
        "purity": "99.9%"
      },
      "certifications": {
        "ISO9001": true,
        "HACCP": true,
        "Organic": true
      },
      "metadata": {
        "temperature_storage": "-20°C",
        "origin_country": "México"
      },
      "createdAt": "2025-08-18T11:30:00.000000Z",
      "updatedAt": "2025-08-18T11:30:00.000000Z"
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
      "warehouseLocation": {
        "data": {
          "type": "warehouse-locations",
          "id": "2"
        }
      }
    }
  }
}
```

---

## **4. PATCH `/api/v1/product-batches/{id}` - Actualizar Batch**

### **Request Body**
```json
{
  "data": {
    "type": "product-batches",
    "id": "1",
    "attributes": {
      "currentQuantity": 800.0000,
      "reservedQuantity": 100.0000,
      "status": "active",
      "qualityNotes": "Actualizado: Revisión de calidad completada",
      "metadata": {
        "temperature_storage": "-18°C",
        "last_inspection": "2025-08-18",
        "quality_rating": "A+"
      }
    }
  }
}
```

### **Response (200 OK)**
```json
{
  "data": {
    "type": "product-batches",
    "id": "1",
    "attributes": {
      "batchNumber": "BATCH-202508-001-01",
      "lotNumber": "LOT123456",
      "manufacturingDate": "2024-06-15T00:00:00.000000Z",
      "expirationDate": "2026-06-15T00:00:00.000000Z",
      "bestBeforeDate": "2026-03-15T00:00:00.000000Z",
      "initialQuantity": 1000.0000,
      "currentQuantity": 800.0000,
      "reservedQuantity": 100.0000,
      "availableQuantity": 700.0000,
      "unitCost": 25.5000,
      "totalValue": 20400.0000,
      "status": "active",
      "supplierName": "Proveedor ABC S.A.",
      "supplierBatch": "SUP12345678",
      "qualityNotes": "Actualizado: Revisión de calidad completada",
      "testResults": {
        "pH": 7.2,
        "moisture": "2.5%"
      },
      "certifications": {
        "ISO9001": true,
        "HACCP": true
      },
      "metadata": {
        "temperature_storage": "-18°C",
        "last_inspection": "2025-08-18",
        "quality_rating": "A+"
      },
      "createdAt": "2025-08-18T10:30:00.000000Z",
      "updatedAt": "2025-08-18T12:00:00.000000Z"
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
      "warehouseLocation": {
        "data": {
          "type": "warehouse-locations",
          "id": "1"
        }
      }
    }
  }
}
```

---

## **5. DELETE `/api/v1/product-batches/{id}` - Eliminar Batch**

### **Request**
```bash
DELETE /api/v1/product-batches/1
```

### **Response (204 No Content)**
```
(Sin contenido - solo status 204)
```

---

## **📋 Validaciones y Campos**

### **Campos Requeridos (POST)**
```json
{
  "batchNumber": "string (único, max:255)",
  "initialQuantity": "number (min:0)",
  "currentQuantity": "number (min:0, ≤ initialQuantity)",
  "unitCost": "number (min:0)",
  "status": "enum (active|expired|quarantine|recalled|consumed)",
  "product": "relationship (obligatorio)",
  "warehouse": "relationship (obligatorio)"
}
```

### **Campos Opcionales**
```json
{
  "lotNumber": "string|null (max:255)",
  "manufacturingDate": "date|null",
  "expirationDate": "date|null (≥ manufacturingDate)",
  "bestBeforeDate": "date|null (≥ manufacturingDate)",
  "reservedQuantity": "number|null (min:0, default:0)",
  "supplierName": "string|null (max:255)",
  "supplierBatch": "string|null (max:255)",
  "qualityNotes": "string|null",
  "testResults": "object|null",
  "certifications": "object|null",
  "metadata": "object|null",
  "warehouseLocation": "relationship|null"
}
```

### **Campos Calculados (Solo Lectura)**
```json
{
  "availableQuantity": "currentQuantity - reservedQuantity",
  "totalValue": "currentQuantity * unitCost",
  "createdAt": "timestamp",
  "updatedAt": "timestamp"
}
```

### **Errores de Validación (422)**
```json
{
  "errors": [
    {
      "status": "422",
      "source": {
        "pointer": "/data/attributes/batchNumber"
      },
      "detail": "The batch number has already been taken."
    },
    {
      "status": "422", 
      "source": {
        "pointer": "/data/attributes/currentQuantity"
      },
      "detail": "The current quantity must not be greater than initial quantity."
    }
  ]
}
```

## **🔐 Permisos Requeridos**

| Endpoint | Permiso Requerido |
|----------|-------------------|
| `GET /product-batches` | `product-batches.index` |
| `GET /product-batches/{id}` | `product-batches.view` |
| `POST /product-batches` | `product-batches.store` |
| `PATCH /product-batches/{id}` | `product-batches.update` |
| `DELETE /product-batches/{id}` | `product-batches.destroy` |

## **💡 Notas Importantes**

1. **Status ENUM**: Solo valores válidos `['active', 'expired', 'quarantine', 'recalled', 'consumed']`
2. **Batch Number**: Debe ser único en toda la tabla
3. **Cantidades**: `currentQuantity` no puede exceder `initialQuantity`
4. **Fechas**: `expirationDate` y `bestBeforeDate` deben ser ≥ `manufacturingDate`
5. **JSON Fields**: `testResults`, `certifications`, `metadata` aceptan cualquier estructura JSON válida
6. **Relationships**: `product` y `warehouse` son obligatorios, `warehouseLocation` es opcional

Esta estructura sigue completamente el estándar **JSON:API 1.1** y es compatible con todos los tests que acabamos de corregir.

---

**Generated:** 2025-08-18  
**Module:** Inventory  
**Entity:** ProductBatch  
**API Version:** JSON:API v1.1