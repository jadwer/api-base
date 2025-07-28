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

### **✅ 2. INVENTORY MODULE - COMPLETADO**
```
Status: ✅ 100% COMPLETADO (ProductBatch implementado - Julio 27, 2025)
Commit: feat(inventory): Complete ProductBatch implementation with full test suite

Entidades migradas:
✅ Warehouses - Bodegas/Almacenes físicos (5 rutas activas)
✅ WarehouseLocations - Ubicaciones dentro de bodegas (15+ campos, 5 rutas)
✅ Stock - Existencias por producto/bodega/ubicación (20+ campos, 5 rutas)
✅ ProductBatches - Lotes con fechas de vencimiento (25+ campos, 5 rutas) - COMPLETADO

Funcionalidades implementadas:
✅ Control de existencias en tiempo real
✅ Gestión de ubicaciones físicas en bodegas  
✅ Campos computados (available_quantity, total_value)
✅ Stock tracking con cantidades reservadas
✅ Gestión completa de lotes con vencimientos y estados
✅ Validaciones de integridad (constraint único product+warehouse+location)
✅ Validaciones de eliminación (WarehouseLocation con stock activo)
✅ Integración completa con JSON:API 5.x

Implementación final (ProductBatch - Julio 27):
✅ CRUD completo con 44/44 tests pasando (257 assertions)
✅ Test suite completo: 5 archivos de test (Index/Show/Store/Update/Destroy)
✅ Validaciones complejas: fechas, cantidades, JSON fields (ArrayHash)
✅ Campos JSON: test_results, certifications, metadata
✅ Estados enum: active, expired, quarantine, recalled, consumed
✅ Sistema de permisos consistente (nomenclatura plural)
✅ Campos computados funcionando correctamente
✅ Factory optimizado con manejo decimal (cast float)
✅ Integración User model con HasRoles trait

Total rutas implementadas: 40 (Product: 20 + Inventory: 20) 
✅ MÓDULO INVENTORY 100% COMPLETADO - ProductBatch finalizado

Commit final: feat(inventory): Complete ProductBatch implementation with full test suite
```
```

---

### **🛒 3. PURCHASE MODULE - COMPLETADO ✅**
```
Status: ✅ 100% COMPLETADO (Julio 28, 2025)

Entidades migradas:
🏪 Suppliers - Proveedores con datos fiscales ✅
📋 PurchaseOrders - Órdenes de compra ✅ 
📦 PurchaseOrderItems - Items de órdenes con cantidades/precios ✅

Relaciones:
- PurchaseOrder → Supplier ✅
- PurchaseOrderItem → PurchaseOrder, Product ✅
- Supplier → PurchaseOrders (hasMany) ✅

Funcionalidades implementadas:
- Gestión completa de proveedores ✅
- CRUD completo para órdenes de compra ✅
- Sistema de items con validaciones ✅
- Control de precios y costos ✅
- Sistema de permisos granular ✅

Métricas finales:
- 3 entidades completamente implementadas
- 141 tests passing (Suppliers: 40, PurchaseOrders: 68, PurchaseOrderItems: 33)
- Todas las validaciones y relaciones funcionando
- Sistema de autorización con guard 'api' corregido

Dependencias: ✅ INVENTORY 100% completado
Completado: Julio 28, 2025
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

### **✅ FASE 1: INVENTORY MODULE - COMPLETADO (100%)**

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

#### **✅ Día 3: JSON:API Implementation - COMPLETADO (4 de 4 entidades)**
- ✅ Implementados 4 Schemas (Warehouse, WarehouseLocation, Stock, ProductBatch)
- ✅ Implementados 4 Authorizers corregidos según MODULE_BLUEPRINT
- ✅ Implementados 4 Requests con validaciones comprehensivas
- ✅ Implementados 4 Resources con relaciones completas
- ✅ Server.php actualizado con todas las entidades

#### **✅ Día 4: ProductBatch Implementation - COMPLETADO (Julio 27, 2025)**
- ✅ Stock CRUD completo implementado (34 tests pasando)
- ✅ ProductBatch CRUD completo implementado (44 tests pasando)
- ✅ Test suite completo: 78 tests total para Inventory
- ✅ Validaciones complejas funcionando (JSON fields, constraints únicos)
- ✅ Sistema de permisos consistente (nomenclatura plural)
- ✅ Campos computados y relaciones optimizadas
- ✅ Factory optimizado con cast float para decimales
- ✅ Integración User model con HasRoles trait

**🏆 RESULTADO: Módulo Inventory 100% completado**
- 4 entidades implementadas completamente
- 20 rutas JSON:API activas
- 78 tests passing con 300+ assertions
- Arquitectura robusta establecida para próximos módulos
- Documentación automática generada con `php artisan api:generate-docs`
- Sistema de documentación organizado en `docs/` implementado

Commit final: feat(inventory): Complete ProductBatch implementation with full test suite

---

### **🎯 FASE 2: PURCHASE MODULE - COMPLETADO ✅**

#### **✅ Día 1-3: Purchase Module Implementation (Julio 28, 2025) - COMPLETADO**
- ✅ Creado módulo Purchase con estructura base
- ✅ Migradas entidades: Supplier, PurchaseOrder, PurchaseOrderItem
- ✅ Implementados JSON:API Schemas, Controllers, Authorizers
- ✅ Desarrollado test suite completo (141 tests passing)
- ✅ Integración con módulos Product e Inventory

#### **✅ Logros principales:**
- Sistema CRUD completo para las 3 entidades
- 141 tests passing con validaciones comprehensivas
- Sistema de relaciones funcionando correctamente
- Autorización con permisos granulares implementada
- **CRÍTICO**: Corregido uso de guard 'api' en authorizers (era causa principal de errores 403)

**🏆 RESULTADO: Módulo Purchase 100% completado**
- 3 entidades implementadas completamente  
- 141 tests passing con 500+ assertions
- Arquitectura JSON:API robusta establecida
- Sistema de autorización corregido y optimizado

Commit final: feat(purchase): Complete Purchase module with all CRUD operations and authorization fixes

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

## 🏆 **ESTADO ACTUAL DEL PROYECTO**

### **✅ MÓDULOS COMPLETADOS (2/4)**
1. **PRODUCT** ✅ - 4 entidades, 20 rutas, 71+ tests (Completado)
2. **INVENTORY** ✅ - 4 entidades, 20 rutas, 78+ tests (Completado Julio 27)

### **🚧 MÓDULO EN DESARROLLO (1/4)**
3. **PURCHASE** 🚧 - 3 entidades, 15 rutas estimadas, 60+ tests (En desarrollo Julio 28)

**Total implementado:** 8 entidades, 40 rutas JSON:API, 149+ tests

### **📊 PRÓXIMOS OBJETIVOS**
1. **PURCHASE MODULE** 🚧 - EN DESARROLLO (3 entidades: Supplier, PurchaseOrder, PurchaseOrderItem)
2. **SALES MODULE** - 3 entidades (Customer, SalesOrder, SalesOrderItem)

### **🔧 ARQUITECTURA ESTABLECIDA**
- ✅ Laravel JSON:API 5.x como base estándar
- ✅ nwidart/laravel-modules para estructura modular
- ✅ Spatie Permission para autorización granular
- ✅ Pattern establecido: Schema → Authorizer → Request → Resource → Controller
- ✅ Sistema de permisos consistente (nomenclatura plural)
- ✅ Test patterns optimizados con jsonApi() helpers
- ✅ Manejo robusto de campos JSON (ArrayHash) y decimales (float cast)

### **📈 APRENDIZAJES CLAVE**
- **Campos JSON:** Usar ArrayHash con arrays asociativos, no ArrayList
- **Decimales:** Cast 'float' en lugar de 'decimal:4' para compatibilidad JSON:API
- **Permisos:** Nomenclatura plural obligatoria (product-batches.*, no product-batch.*)
- **Tests:** Validaciones específicas para JSON:API con withData() syntax
- **User Integration:** HasRoles trait requerido para sistema de permisos

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

**Próximo paso:** 🚧 PURCHASE MODULE en desarrollo - Implementar Supplier, PurchaseOrder, PurchaseOrderItem con arquitectura JSON:API establecida ⚡
