# 🚀 MIGRATION ROADMAP - lwm-crm-api → api-base

**Objetivo:** Migrar completamente el proyecto `lwm-crm-api` a la estructura modular `api-base` usando Laravel JSON:API 5.x con nwidart/laravel-modules.

**Fecha de inicio:** Julio 2025  
**Estado actual:** Módulos Product ✅ e Inventory ✅ completados

📖 **Para especificaciones técnicas detalladas:** Ver [MODULE_BLUEPRINT.md](./MODULE_BLUEPRINT.md)

---

## 📋 **ANÁLISIS ESTRUCTURAL COMPLETO**

### **Entidades identificadas en lwm-crm-api:**
```
📦 PRODUCTS & CATALOG:
- Product.php ✅
- Unit.php ✅  
- Category.php ✅
- Brand.php ✅

📊 INVENTORY & WAREHOUSES:
- Warehouse.php ✅
- WarehouseLocation.php ✅
- Stock.php ✅
- ProductBatch.php

🛒 PURCHASES:
- Supplier.php
- PurchaseOrder.php  
- PurchaseOrderItem.php

💰 SALES:
- Customer.php
- SalesOrder.php
- SalesOrderItem.php

👥 USERS & AUTH:
- User.php (ya existe en base)
```

---

## 🎯 **PLAN DE MÓDULOS A IMPLEMENTAR**

### **✅ 1. PRODUCT MODULE - COMPLETADO**
```
Status: ✅ 100% COMPLETADO (Commit: feat: Complete CRUD operations for Product module)

Entidades migradas:
✅ Products - Productos principales con SKU, precios, IVA
✅ Units - Unidades de medida (kg, lt, pz, etc.)
✅ Categories - Categorías de productos  
✅ Brands - Marcas de productos

Funcionalidades:
✅ CRUD completo con Laravel JSON:API 5.x
✅ Sistema de autorización con 20 permisos granulares
✅ Validaciones robustas (campos únicos, requeridos)
✅ Tests comprehensivos: 71 tests (Index/Show/Store/Update/Delete)
✅ Relaciones: Product → Unit, Category, Brand
✅ Factories para datos de prueba
✅ Schema-driven Resources automáticos

Arquitectura establecida:
✅ Controllers con Actions traits (FetchMany, FetchOne, Store, Update, Destroy)
✅ Authorizers para control de acceso granular
✅ JSON:API Resources automáticos por Schema
✅ Test patterns con withData() syntax
✅ Modular structure con nwidart/laravel-modules
```

---

### **🔄 2. INVENTORY MODULE - EN PROGRESO**
```
Status: 🔄 75% COMPLETADO (Stock implementado - Julio 27, 2025)

Entidades migradas:
✅ Warehouses - Bodegas/Almacenes físicos (5 rutas activas)
✅ WarehouseLocations - Ubicaciones dentro de bodegas (15+ campos, 5 rutas)
✅ Stock - Existencias por producto/bodega/ubicación (20+ campos, 5 rutas) - RECIÉN COMPLETADO
❌ ProductBatches - Lotes con fechas de vencimiento (25+ campos, 5 rutas) - PENDIENTE

Funcionalidades implementadas:
✅ Control de existencias en tiempo real
✅ Gestión de ubicaciones físicas en bodegas  
✅ Campos computados (available_quantity, total_value)
✅ Stock tracking con cantidades reservadas
✅ Validaciones de integridad (constraint único product+warehouse+location)
✅ Validaciones de eliminación (WarehouseLocation con stock activo)
✅ Integración completa con JSON:API 5.x

Últimas implementaciones (Stock - Julio 27):
✅ CRUD completo con 34/34 tests pasando
✅ Validaciones únicas funcionando correctamente (422 responses)
✅ Sistema de permisos consistente (nomenclatura plural)
✅ Integridad de datos con WarehouseLocation
✅ Factory con manejo correcto de decimales
✅ Esquema JSON:API con tipos de campo correctos

Arquitectura establecida:
✅ Schemas con mapping camelCase ↔ snake_case
✅ Authorizers con interfaz correcta (LaravelJsonApi\Contracts\Auth\Authorizer)
✅ Requests con validaciones comprehensivas y constraint únicos
✅ Resources con relaciones completas
✅ 15 rutas activas confirmadas (5 por entidad completada)
✅ Modelos alineados con migraciones
✅ Permisos granulares (15 permisos inventory implementados)

Total rutas implementadas: 35 (Product: 20 + Inventory: 15) 
FALTA: ProductBatch (5 rutas más) para completar módulo

Commit actual: feat(inventory): Implement complete Stock CRUD with validations and fix WarehouseLocation deletion
```
```

---

### **🛒 3. PURCHASE MODULE - SIGUIENTE PRIORIDAD**
```
Status: ❌ PENDIENTE (Próximo después de completar ProductBatch)

Entidades a migrar:
🏪 Suppliers - Proveedores con datos fiscales
📋 PurchaseOrders - Órdenes de compra  
📦 PurchaseOrderItems - Items de órdenes con cantidades/precios

Relaciones:
- PurchaseOrder → Supplier
- PurchaseOrderItem → PurchaseOrder, Product
- Supplier → PurchaseOrders (hasMany)

Funcionalidades:
- Gestión de proveedores y condiciones
- Flujo: Cotización → Orden → Recepción → Facturación
- Actualización automática de inventario al recibir
- Control de precios y costos de compra
- Estados de órdenes (pendiente, parcial, completa)

Dependencias: 🔄 INVENTORY 75% completado (falta ProductBatch)
Estimación: 2-3 días de desarrollo + tests
```

---

### **💰 4. SALES MODULE - DESPUÉS DE PURCHASE**  
```
Status: ❌ PENDIENTE

Entidades a migrar:
👥 Customers - Clientes con clasificación y límites
📄 SalesOrders - Órdenes de venta y cotizaciones
📦 SalesOrderItems - Items con precios y descuentos

Relaciones:
- SalesOrder → Customer  
- SalesOrderItem → SalesOrder, Product
- Customer → SalesOrders (hasMany)

Funcionalidades:
- Gestión de clientes (mayorista, minorista, crédito)
- Flujo: Cotización → Pedido → Aprobación → Entrega → Facturación  
- Validación de stock en tiempo real
- Control de límites de crédito
- Cálculo de precios, descuentos, promociones
- Reserva automática de inventario

Dependencias: Requiere INVENTORY y PURCHASE completados  
Estimación: 3-4 días de desarrollo + tests
```

---

## ⚡ **PLAN DE TRABAJO DETALLADO**

### **🔄 FASE 1: INVENTORY MODULE - EN PROGRESO (75% COMPLETADO)**

#### **✅ Día 1: Estructura base - COMPLETADO**
- ✅ Creado módulo Inventory  
- ✅ Migrados modelos: Warehouse, WarehouseLocation, Stock, ProductBatch
- ✅ Definidas relaciones entre entidades
- ✅ Migraciones ejecutadas con foreign keys apropiadas
- ✅ Estructura DB verificada

#### **✅ Día 2: Funcionalidades core - COMPLETADO**
- ✅ Creados JSON:API Schemas para cada entidad (4 schemas)
- ✅ Implementados Controllers con rutas JSON:API
- ✅ Configurados Authorizers con permisos granulares
- ✅ Permisos creados en InventoryPermissionSeeder
- ✅ Modelo-migración alignment completado

#### **✅ Día 3: JSON:API Implementation - COMPLETADO (3 de 4 entidades)**
- ✅ Implementados 3 Schemas (Warehouse, WarehouseLocation, Stock)
- ✅ Implementados 3 Authorizers corregidos según MODULE_BLUEPRINT
- ✅ Implementados 3 Requests con validaciones comprehensivas
- ✅ Implementados 3 Resources con relaciones completas
- ✅ Server.php actualizado con entidades completadas

#### **✅ Día 4: Stock Implementation - COMPLETADO (Julio 27, 2025)**
- ✅ Stock CRUD completado con 34/34 tests pasando
- ✅ Validaciones únicas implementadas con responses 422
- ✅ Sistema de permisos corregido (nomenclatura plural)
- ✅ Factory rebuildeado para manejo de decimales
- ✅ Integridad de datos con WarehouseLocation establecida
- ✅ Commit: feat(inventory): Implement complete Stock CRUD with validations

#### **❌ Día 5: ProductBatch Implementation - PENDIENTE**
- ❌ ProductBatch CRUD (falta implementar)
- ❌ Validaciones de fechas de vencimiento
- ❌ Relaciones con Stock y Product
- ❌ Tests comprehensivos para ProductBatch
- ❌ Finalización del módulo Inventory

**� PRÓXIMO OBJETIVO: Completar ProductBatch para finalizar INVENTORY MODULE**

---

### **🎯 FASE 2: PURCHASE MODULE (Próximo)**

#### **Estructura similar:**
- Migración de entidades Supplier, PurchaseOrder, PurchaseOrderItem
- Implementación de flujo de compras
- Integración con Inventory (actualización de stock)
- Tests de flujo completo: Orden → Recepción → Inventario

### **FASE 3: SALES MODULE (Final)**

#### **Estructura similar:**  
- Migración de Customer, SalesOrder, SalesOrderItem
- Flujo de ventas con validación de inventario
- Cálculo de precios y descuentos
- Integración completa con todos los módulos

---

## 🏗️ **ARQUITECTURA Y PATRONES**

📖 **Para especificaciones técnicas completas de creación de módulos, ver:** [MODULE_BLUEPRINT.md](./MODULE_BLUEPRINT.md)

### **Resumen del patrón establecido (Product Module):**

- ✅ **Laravel JSON:API 5.x** con Schemas, Controllers Actions, Authorizers
- ✅ **Estructura modular** con nwidart/laravel-modules  
- ✅ **Sistema de permisos** granular con spatie/laravel-permission
- ✅ **Testing comprehensivo** con JSON:API testing traits
- ✅ **Factories y Seeders** para datos de prueba
- ✅ **Validaciones robustas** con Form Requests

---

## 🎯 **MÉTRICAS DE ÉXITO**

### **Por cada módulo completado:**
- [ ] ✅ Todas las entidades migradas con relaciones correctas
- [ ] ✅ CRUD completo funcional via JSON:API
- [ ] ✅ Sistema de autorización implementado  
- [ ] ✅ Validaciones de negocio apropiadas
- [ ] ✅ Suite de tests completa (mínimo 50+ tests por módulo)
- [ ] ✅ Documentación actualizada
- [ ] ✅ Commit limpio con descripción detallada

### **Proyecto completo:**
- [ ] ✅ 4 módulos principales migrados
- [ ] ✅ +300 tests funcionando
- [ ] ✅ API JSON:API 5.x completamente funcional  
- [ ] ✅ Sistema de permisos robusto
- [ ] ✅ Integración entre módulos sin errores
- [ ] ✅ Listo para implementar frontend

---

## 📚 **RECURSOS Y COMANDOS ÚTILES**

### **Comandos principales:**
```bash
# Crear nuevo módulo
php artisan module:make {ModuleName}

# Ejecutar tests específicos del módulo
php artisan test Modules/{Module}/Tests/Feature/

# Ejecutar seeders del módulo
php artisan db:seed --class=Modules\\{Module}\\Database\\Seeders\\{Module}DatabaseSeeder

# Verificar permisos y rutas
php artisan permission:show
php artisan route:list --path=api/v1
```

### **Documentación clave:**
- 📖 [MODULE_BLUEPRINT.md](./MODULE_BLUEPRINT.md) - Guía técnica completa
- [Laravel JSON:API 5.x](https://laraveljsonapi.io/5.x/) - Documentación oficial
- [nwidart Modules](https://nwidart.com/laravel-modules/) - Módulos Laravel
- [Spatie Permissions](https://spatie.be/docs/laravel-permission/) - Sistema de permisos

---

## 🚨 **NOTAS IMPORTANTES**

1. **Seguir siempre el patrón establecido** - Ver [MODULE_BLUEPRINT.md](./MODULE_BLUEPRINT.md)
2. **Estructura consistente** - Respetar namespaces y carpetas establecidas
3. **Tests primero** - No avanzar sin cobertura completa del CRUD  
4. **Commits frecuentes** con descripciones detalladas
5. **Validar integración** con otros módulos antes de commit final
6. **Documentar cambios** importantes en este archivo

### **⚠️ Recordatorios Laravel JSON:API 5.x:**
- Schemas con `public static string $model` 
- Authorizers implementan `Authorizer` interface
- Relaciones requieren `->type()` explícito
- Controllers extienden `Controller` base
- Tests usan `$this->jsonApi()->withData()`

---

**Próximo paso:** Completar ProductBatch para finalizar INVENTORY MODULE, luego implementar PURCHASE MODULE ⏳
