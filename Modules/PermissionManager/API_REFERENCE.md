# 📘 API Reference - PermissionManager

## 🧩 Base URL
`/api/v1`

---

## 🛠 Endpoints disponibles

### 📁 Roles (`roles`)

| Método | Endpoint             | Descripción                                      | Permiso necesario         |
|--------|----------------------|--------------------------------------------------|----------------------------|
| GET    | /roles               | Lista todos los roles                            | `roles.index`              |
| GET    | /roles/{id}          | Muestra un rol específico                        | `roles.view`               |
| POST   | /roles               | Crea un nuevo rol                                | `roles.store`              |
| PATCH  | /roles/{id}          | Actualiza un rol (atributos y/o permisos)        | `roles.update`             |
| DELETE | /roles/{id}          | Elimina un rol                                   | `roles.destroy`            |
| GET    | /roles/{id}/permissions            | Obtiene permisos relacionados       | `roles.view`               |
| GET    | /roles/{id}/relationships/permissions | Relación para sync de permisos | `roles.view`               |
| PATCH  | /roles/{id}/relationships/permissions | Sync directo (opcional, si se usa) | `roles.update`             |

> ✅ Permite `?include=permissions` para incluir permisos en el mismo response.

### 📁 Permisos (`permissions`)

| Método | Endpoint             | Descripción                                      | Permiso necesario         |
|--------|----------------------|--------------------------------------------------|----------------------------|
| GET    | /permissions         | Lista todos los permisos                         | `permissions.index`        |
| GET    | /permissions/{id}    | Muestra un permiso específico                    | `permissions.view`         |
| POST   | /permissions         | Crea un nuevo permiso                            | `permissions.store`        |
| PATCH  | /permissions/{id}    | Actualiza un permiso                             | `permissions.update`       |
| DELETE | /permissions/{id}    | Elimina un permiso                               | `permissions.destroy`      |

---

## 🔄 Comportamiento de Actualización (`PATCH`)

- **Roles:**
  - Atributos editables: `name`, `description`, `guard_name`.
  - Permite actualizar permisos relacionados directamente dentro de la sección `relationships`.
  - Se utiliza `withRelationship()` dentro de `RoleRequest` para sincronizar permisos (`syncPermissions()`).
  - Validación de unicidad de `name` usando `Rule::unique(...)->ignoreModel($this->model())`.

- **Permisos:**
  - Atributos editables: `name`, `guard_name`.

---

## 📦 Estructura JSON esperada

### 🎯 Crear / Actualizar `Role`

```json
{
  "data": {
    "type": "roles",
    "attributes": {
      "name": "editor",
      "description": "Rol de edición",
      "guard_name": "api"
    },
    "relationships": {
      "permissions": {
        "data": [
          { "type": "permissions", "id": "1" },
          { "type": "permissions", "id": "2" }
        ]
      }
    }
  }
}
```

### 🔎 Respuesta con `include=permissions`

```json
{
  "data": {
    "type": "roles",
    "id": "1",
    "attributes": {
      "name": "editor",
      "description": "Rol de edición",
      "guard_name": "api",
      "createdAt": "...",
      "updatedAt": "..."
    },
    "relationships": {
      "permissions": {
        "data": [
          { "type": "permissions", "id": "1" },
          { "type": "permissions", "id": "2" }
        ]
      }
    }
  },
  "included": [
    {
      "type": "permissions",
      "id": "1",
      "attributes": {
        "name": "roles.view",
        "guard_name": "api",
        "createdAt": "...",
        "updatedAt": "..."
      }
    }
  ]
}
```

---

## ⚠️ Validaciones clave

- `name` en `Role` debe ser único → se ignora al actualizar el mismo registro.
- `permissions` debe ser una relación **to-many** válida.
- Formato `Content-Type` y `Accept`: `application/vnd.api+json`
- JSON malformado → error 400.
- Campos requeridos ausentes → error 422.