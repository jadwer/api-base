# Integración Laravel JSON:API 5.x con Spatie Laravel Permission

## Problema Resuelto

Se solucionó la integración entre Laravel JSON:API 5.x y spatie/laravel-permission para manejar correctamente las relaciones many-to-many de usuarios y roles.

### Error Original
```
SQLSTATE[HY000]: General error: 1364 Field 'model_type' doesn't have a default value
```

## Cambios Implementados

### 1. Configuración de Spatie Permission (`config/permission.php`)
```php
'models' => [
    'permission' => Modules\PermissionManager\Models\Permission::class,
    'role' => Modules\PermissionManager\Models\Role::class,
],
```

### 2. Modelo User (`Modules/User/app/Models/User.php`)
- Eliminada relación personalizada `roles()` que interfería con el trait `HasRoles`
- El trait `HasRoles` de Spatie maneja automáticamente la relación polimórfica

### 3. UserSchema (`Modules/User/app/JsonApi/V1/Users/UserSchema.php`)
```php
Str::make('role')
    ->readOnly()
    ->serializeUsing(function ($model, $column) {
        return $model->getRoleNames()->first();
    }),
BelongsToMany::make('roles')->type('roles'),
```

### 4. UserRequest (`Modules/User/app/JsonApi/V1/Users/UserRequest.php`)
- Removido campo `role` de validación directa
- Mantenida validación de relación `roles`

### 5. UserObserver (`Modules/User/app/Observers/UserObserver.php`)
- Creado para manejar asignación de roles post-creación/actualización
- Registrado en `UserServiceProvider`

### 6. Tests Actualizados (`Modules/User/tests/Feature/UserUpdateTest.php`)
- Actualizados para usar `relationships` en lugar de atributos directos
- Agregados tests para múltiples roles y eliminación de roles
- **30 tests pasando** con **181 assertions**

## Uso de la API

### Crear/Actualizar Usuario con Roles
```json
{
  "data": {
    "type": "users",
    "id": "3",
    "attributes": {
      "name": "Nombre Usuario",
      "email": "usuario@ejemplo.com",
      "status": "active"
    },
    "relationships": {
      "roles": {
        "data": [
          {
            "type": "roles",
            "id": "1"
          }
        ]
      }
    }
  }
}
```

### Ejemplo cURL
```bash
curl -X PATCH "http://localhost:8000/api/v1/users/3" \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -H "Authorization: Bearer TOKEN" \
  -d @payload.json
```

## Características

✅ **Relaciones Many-to-Many funcionando**
✅ **Campo `role` de solo lectura** muestra el rol principal
✅ **Relación `roles`** para asignación múltiple
✅ **Compatibilidad total** con Laravel JSON:API 5.x
✅ **Tests comprehensivos** para todas las operaciones
✅ **Seeders funcionando** sin errores
✅ **Spatie Permission integrado** correctamente

## Comandos de Verificación

```bash
# Ejecutar seeders
php artisan module:seed PermissionManager
php artisan module:seed User

# Ejecutar tests
php artisan test --filter=UserUpdateTest

# Verificar roles en base de datos
php artisan tinker --execute="echo 'Roles: '; Modules\PermissionManager\Models\Role::all(['id', 'name'])->each(function(\$r) { echo \$r->id . ' - ' . \$r->name . PHP_EOL; });"
```

## Arquitectura Final

```
User Model (HasRoles trait)
    ↓
UserSchema (JSON:API)
    ├── role (read-only, first role)
    └── roles (relationship, many-to-many)
        ↓
UserObserver (role assignment logic)
    ↓
Spatie Permission (automatic pivot management)
```

La integración está completamente funcional y siguiendo las mejores prácticas de Laravel JSON:API 5.x y Spatie Permission.
