# Sales Module

## Descripción
Módulo de gestión de ventas que incluye entidades Customer, SalesOrder y SalesOrderItem con completa funcionalidad CRUD y sistema de autorizaciones.

## Estado del Módulo

### ✅ Customer Entity (COMPLETADO - 100%)
**Tests:** 49/49 pasando (100%)

#### Funcionalidades implementadas:
- ✅ **CRUD completo** (Create, Read, Update, Delete)
- ✅ **Sistema de autorizaciones** con roles diferenciados
- ✅ **Validaciones robustas** de datos de entrada
- ✅ **Filtros y ordenamiento** por múltiples campos
- ✅ **Paginación** automática
- ✅ **Relaciones** con SalesOrders
- ✅ **Metadata** flexible en formato JSON

#### Test Suites:
- **CustomerIndexTest:** 9/9 tests ✅ (listado, filtros, paginación, autorizaciones)
- **CustomerShowTest:** 9/9 tests ✅ (visualización, relaciones, autorizaciones)
- **CustomerStoreTest:** 10/10 tests ✅ (creación, validaciones, duplicados)
- **CustomerUpdateTest:** 11/11 tests ✅ (actualización completa/parcial, validaciones)
- **CustomerDestroyTest:** 10/10 tests ✅ (eliminación, autorizaciones, idempotencia)

#### Autorizaciones por Rol:
- **God/Admin:** CRUD completo ✅
- **Tech:** CRUD completo ✅  
- **Customer:** Sin acceso a otros customers ✅
- **Guest:** Sin acceso ✅

### 🚧 SalesOrder Entity (PENDIENTE)
**Estado:** Por implementar

### 🚧 SalesOrderItem Entity (PENDIENTE)  
**Estado:** Por implementar

## Arquitectura

### JSON API v1.1 Compliance
- ✅ **Schemas** con tipado estricto
- ✅ **Resources** para serialización
- ✅ **Requests** con validación personalizada
- ✅ **Authorizers** con lógica de roles
- ✅ **Filtros** Where con campos indexados
- ✅ **Paginación** PagePagination

### Base de Datos
```sql
-- Customers table
- id (bigint, primary key)
- name (varchar, required)
- email (varchar, unique, required)
- phone (varchar, nullable)
- address (text, nullable)
- city (varchar, nullable)
- state (varchar, nullable)
- country (varchar, nullable)
- classification (enum: 'individual', 'business', 'premium')
- credit_limit (decimal, default 0)
- current_credit (decimal, default 0)
- is_active (boolean, default true)
- metadata (json, nullable)
- timestamps
```

### Permisos del Sistema
```php
// Customer permissions
'customers.index'   // Listar customers
'customers.show'    // Ver customer individual
'customers.store'   // Crear customer
'customers.update'  // Actualizar customer
'customers.destroy' // Eliminar customer
```

## Patrones de Testing

### Enfoque Clean Testing
- ✅ **Helper methods** para usuarios (`getAdminUser()`, `getTechUser()`, `getCustomerUser()`)
- ✅ **Assertions consistentes** usando JSON API testing
- ✅ **Snake_case** en todos los campos de datos
- ✅ **Cobertura completa** de casos edge y validaciones
- ✅ **Autorización granular** por cada operación

### Estructura de Tests
```php
// Patrón estándar aplicado:
public function test_admin_can_perform_action(): void
{
    $admin = $this->getAdminUser();
    
    $response = $this->actingAs($admin, 'sanctum')
        ->jsonApi()
        ->expects('customers')
        ->get('/api/v1/customers');
        
    $response->assertOk();
}
```

## Comandos Útiles

### Testing
```bash
# Todos los tests de Customer
php artisan test --filter=Customer

# Test específico
php artisan test --filter=CustomerIndexTest

# Con coverage
php artisan test --filter=Customer --coverage
```

### Seeders
```bash
# Re-ejecutar permisos de Sales
php artisan db:seed --class="Modules\Sales\Database\Seeders\SalesAssignPermissionsSeeder"

# Todos los seeders del módulo
php artisan module:seed Sales
```

### Documentación
```bash
# Generar documentación API
php artisan api:generate-docs
```

## Próximos Pasos

1. **SalesOrder Entity**
   - Implementar CRUD completo
   - Tests con mismo patrón de Customer
   - Relaciones con Customer y SalesOrderItems
   
2. **SalesOrderItem Entity** 
   - Implementar CRUD completo
   - Tests con mismo patrón establecido
   - Relaciones con SalesOrder y Products

3. **Integración completa**
   - Tests de integración entre entidades
   - Workflow completo de ventas
   - Reportes y métricas

## Lecciones Aprendidas

### ✅ Mejores Prácticas Aplicadas:
- **Reescritura limpia** vs reparación de tests complejos (más eficiente en tokens)
- **Patrones consistentes** de helper methods en tests
- **Autorización granular** por rol en lugar de permisos genéricos
- **Naming conventions** JSON API (singular para Schema/Authorizer)
- **Validación exhaustiva** de casos edge y datos inválidos

### 🔧 Problemas Resueltos:
- ❌ Tests originales con lógica compleja de autorización
- ❌ Authorizer con bypass en ambiente testing
- ❌ Inconsistencias camelCase/snake_case
- ❌ Naming plural incorrecto en Authorizers

---

**Módulo desarrollado siguiendo estándares de Laravel JSON API v1.1 y mejores prácticas de testing**
