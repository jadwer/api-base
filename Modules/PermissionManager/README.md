# ✅ Módulo PermissionManager

**Stack:** Laravel 12 · Laravel JSON:API 5.1 · Spatie Permissions · Sanctum  
**Estatus:** 100% funcional · Validado con tests

---

## 📁 Estructura General

| Componente                | Descripción                                                                 |
|--------------------------|-----------------------------------------------------------------------------|
| `Role` / `Permission`    | Modelos extendidos desde Spatie; define `permissions()` como relación       |
| `RoleController`         | CRUD delegado al `Request` (usa traits oficiales JSON:API)                  |
| `PermissionController`   | CRUD directo vía JSON:API                                                   |
| `RoleRequest`            | Validaciones + sincronización de `permissions`                             |
| `PermissionRequest`      | Validaciones básicas (`name`, `guard_name`)                                |
| `RoleSchema`             | Expone `permissions` como `BelongsToMany`                                  |
| `PermissionSchema`       | Atributos clave (`name`, `guard_name`, timestamps)                         |
| `RoleResource`           | Muestra relaciones `permissions` en includes                               |
| `PermissionResource`     | Salida básica JSON:API                                                      |

---

## ✅ Funcionalidades Completas

| Función                              | Estado  | Detalles                                                    |
|--------------------------------------|---------|-------------------------------------------------------------|
| CRUD Roles (`index`, `show`, etc)    | ✅      | Relación con `permissions` incluida                         |
| CRUD Permisos                        | ✅      | Independiente y completo                                    |
| Asignar permisos en store de rol     | ✅      | vía `relationships.permissions`                             |
| Actualizar permisos en rol           | ✅      | sync mediante PATCH                                         |
| Mostrar permisos en includes         | ✅      | con `?include=permissions` en `index` y `show`              |
| Validaciones JSON:API                | ✅      | 400 (formato), 422 (errores de datos), relaciones válidas  |
| Autorización                         | ✅      | Validación por Spatie y middleware                          |

---

## 🧪 Pruebas Funcionales

| Test File                                | Descripción                                                  |
|------------------------------------------|--------------------------------------------------------------|
| `F1PermissionTest.php`                   | CRUD completo para permisos                                  |
| `F1RoleTest.php`                         | CRUD básico de roles                                         |
| `F2RoleStoreWithPermissionsTest.php`     | Store de rol con permisos, errores de formato y permisos     |
| `F2RoleUpdatePermissionsTest.php`        | Actualización de permisos (sin modificar nombre)             |
| `F3RoleIncludePermissionsTest.php`       | Validación de `?include=permissions`                         |

**Resultado:**
```bash
php artisan test --filter=F1,F2,F3

Tests: 105 passed (1318 assertions)
```

---

## 🔐 Permisos Registrados

```php
[
  'roles.index', 'roles.show', 'roles.store', 'roles.update', 'roles.destroy',
  'permissions.index', 'permissions.show', 'permissions.store', 'permissions.update', 'permissions.destroy',
  'permissions.assign' // si se decide controlar asignación en un permiso dedicado
]
```

---

## 🧠 Notas Técnicas

- `RoleRequest::rules()` usa:
  ```php
  Rule::unique('roles', 'name')->ignoreModel($this->model())
  ```
  para permitir actualizaciones sin cambiar el nombre.

- Relación `permissions`:
  - Se valida con `relationshipRules()`
  - Se sincroniza con `withRelationship()`

- Compatible 100% con [Laravel JSON:API 5.1](https://laraveljsonapi.io/5.x/)

---

## 📦 Listo para producción

Este módulo se encuentra estable, probado, desacoplado y listo para integrarse a cualquier backend modular. Compatible con `nwidart/laravel-modules`.

---


## 📊 Métricas

- **Test Files**: 13
- **Generated**: 2025-08-19 17:59:33
- **Status**: ✅ Documentation up to date
- **API Version**: JSON:API v1.0
