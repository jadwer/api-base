# PermissionManager Module - Frontend Integration Guide

**Module:** PermissionManager
**Entities:** 2 (Role, Permission)
**Endpoints:** 10 CRUD + relationship management
**Base Path:** `/api/v1`

## Overview

The PermissionManager module manages roles and permissions using Spatie Laravel Permission. Provides CRUD for both entities and allows assigning permissions to roles via JSON:API relationships.

## Core Entities

### 1. Role

**Endpoint:** `/roles`
**Resource Type:** `roles`

#### TypeScript Interface

```typescript
interface Role {
  id: string;
  name: string;
  description: string | null;
  guardName: string;
  createdAt: string;
  updatedAt: string;
}

interface RoleCreateRequest {
  name: string;
  description?: string;
  guardName: 'api';
  permissionIds?: string[];
}

interface RoleUpdateRequest {
  name?: string;
  description?: string;
  guardName?: 'api';
  permissionIds?: string[];
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Notes |
|---------------|-----------------|------|----------|----------|-------|
| `name` | `name` | string | Yes | Yes | unique |
| `description` | `description` | string | No | No | nullable |
| `guard_name` | `guard_name` | string | Yes | No | must be 'api' |
| `createdAt` | `created_at` | datetime | No | Yes | read-only |
| `updatedAt` | `updated_at` | datetime | No | Yes | read-only |

#### Relationships

- `permissions` → Permission[] (belongsToMany)

---

### 2. Permission

**Endpoint:** `/permissions`
**Resource Type:** `permissions`

#### TypeScript Interface

```typescript
interface Permission {
  id: string;
  name: string;
  guardName: string;
  createdAt: string;
  updatedAt: string;
}

interface PermissionCreateRequest {
  name: string;
  guard_name: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Notes |
|---------------|-----------------|------|----------|----------|-------|
| `name` | `name` | string | Yes | Yes | e.g., 'users.index' |
| `guard_name` | `guard_name` | string | Yes | No | typically 'api' |
| `createdAt` | `created_at` | datetime | No | Yes | read-only |
| `updatedAt` | `updated_at` | datetime | No | Yes | read-only |

---

## API Endpoints

### Roles

#### List Roles

```http
GET /api/v1/roles
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

**Note:** Roles are automatically loaded with their permissions (via `with()` in schema).

#### Response

```json
{
  "data": [
    {
      "type": "roles",
      "id": "1",
      "attributes": {
        "name": "admin",
        "description": "Administrator with full access",
        "guard_name": "api",
        "createdAt": "2024-01-01T00:00:00Z",
        "updatedAt": "2024-01-01T00:00:00Z"
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
  ],
  "included": [
    {
      "type": "permissions",
      "id": "1",
      "attributes": {
        "name": "users.index",
        "guard_name": "api"
      }
    }
  ]
}
```

---

#### Create Role with Permissions

```http
POST /api/v1/roles
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
```

```json
{
  "data": {
    "type": "roles",
    "attributes": {
      "name": "editor",
      "description": "Content editor role",
      "guard_name": "api"
    },
    "relationships": {
      "permissions": {
        "data": [
          { "type": "permissions", "id": "10" },
          { "type": "permissions", "id": "11" },
          { "type": "permissions", "id": "12" }
        ]
      }
    }
  }
}
```

---

#### Update Role Permissions

```http
PATCH /api/v1/roles/{id}/relationships/permissions
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
```

```json
{
  "data": [
    { "type": "permissions", "id": "1" },
    { "type": "permissions", "id": "2" },
    { "type": "permissions", "id": "3" }
  ]
}
```

**Note:** This replaces all permissions. To add or remove individual permissions, use the specific relationship endpoints.

---

### Permissions

#### List Permissions

```http
GET /api/v1/permissions
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

#### Response

```json
{
  "data": [
    {
      "type": "permissions",
      "id": "1",
      "attributes": {
        "name": "users.index",
        "guard_name": "api",
        "createdAt": "2024-01-01T00:00:00Z",
        "updatedAt": "2024-01-01T00:00:00Z"
      }
    }
  ],
  "meta": {
    "page": {
      "currentPage": 1,
      "perPage": 15,
      "total": 150
    }
  }
}
```

---

## TypeScript Service

```typescript
interface RoleResource {
  type: 'roles';
  id: string;
  attributes: {
    name: string;
    description: string | null;
    guard_name: string;
    createdAt: string;
    updatedAt: string;
  };
  relationships?: {
    permissions?: {
      data: { type: 'permissions'; id: string }[];
    };
  };
}

interface PermissionResource {
  type: 'permissions';
  id: string;
  attributes: {
    name: string;
    guard_name: string;
    createdAt: string;
    updatedAt: string;
  };
}

class PermissionManagerService {
  private baseUrl = '/api/v1';

  // === Roles ===

  async listRoles(): Promise<{ data: RoleResource[]; included?: PermissionResource[] }> {
    const response = await fetch(`${this.baseUrl}/roles`, {
      headers: this.getHeaders(),
    });
    return response.json();
  }

  async getRole(id: string): Promise<RoleResource> {
    const response = await fetch(`${this.baseUrl}/roles/${id}?include=permissions`, {
      headers: this.getHeaders(),
    });
    const result = await response.json();
    return result.data;
  }

  async createRole(data: {
    name: string;
    description?: string;
    permissionIds?: string[];
  }): Promise<RoleResource> {
    const payload: any = {
      data: {
        type: 'roles',
        attributes: {
          name: data.name,
          description: data.description || null,
          guard_name: 'api',
        },
      },
    };

    if (data.permissionIds?.length) {
      payload.data.relationships = {
        permissions: {
          data: data.permissionIds.map(id => ({ type: 'permissions', id })),
        },
      };
    }

    const response = await fetch(`${this.baseUrl}/roles`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    return result.data;
  }

  async updateRole(id: string, data: {
    name?: string;
    description?: string;
  }): Promise<RoleResource> {
    const attributes: any = {};
    if (data.name !== undefined) attributes.name = data.name;
    if (data.description !== undefined) attributes.description = data.description;

    const response = await fetch(`${this.baseUrl}/roles/${id}`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify({
        data: {
          type: 'roles',
          id,
          attributes,
        },
      }),
    });
    const result = await response.json();
    return result.data;
  }

  async deleteRole(id: string): Promise<void> {
    await fetch(`${this.baseUrl}/roles/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });
  }

  async updateRolePermissions(roleId: string, permissionIds: string[]): Promise<void> {
    await fetch(`${this.baseUrl}/roles/${roleId}/relationships/permissions`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify({
        data: permissionIds.map(id => ({ type: 'permissions', id })),
      }),
    });
  }

  // === Permissions ===

  async listPermissions(params?: {
    page?: number;
    perPage?: number;
  }): Promise<{ data: PermissionResource[]; meta: any }> {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.set('page[number]', params.page.toString());
    if (params?.perPage) queryParams.set('page[size]', params.perPage.toString());

    const response = await fetch(
      `${this.baseUrl}/permissions?${queryParams.toString()}`,
      { headers: this.getHeaders() }
    );
    return response.json();
  }

  async getPermission(id: string): Promise<PermissionResource> {
    const response = await fetch(`${this.baseUrl}/permissions/${id}`, {
      headers: this.getHeaders(),
    });
    const result = await response.json();
    return result.data;
  }

  async createPermission(name: string, guardName = 'api'): Promise<PermissionResource> {
    const response = await fetch(`${this.baseUrl}/permissions`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({
        data: {
          type: 'permissions',
          attributes: {
            name,
            guard_name: guardName,
          },
        },
      }),
    });
    const result = await response.json();
    return result.data;
  }

  async deletePermission(id: string): Promise<void> {
    await fetch(`${this.baseUrl}/permissions/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });
  }

  // === Helpers ===

  private getHeaders(): Record<string, string> {
    return {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json',
    };
  }

  /**
   * Group permissions by module for UI display
   */
  groupPermissionsByModule(permissions: PermissionResource[]): Record<string, PermissionResource[]> {
    return permissions.reduce((acc, permission) => {
      const [module] = permission.attributes.name.split('.');
      if (!acc[module]) acc[module] = [];
      acc[module].push(permission);
      return acc;
    }, {} as Record<string, PermissionResource[]>);
  }
}

export const permissionManagerService = new PermissionManagerService();
```

---

## Permission Naming Convention

Permissions follow the pattern: `{entity}.{action}`

| Pattern | Example | Description |
|---------|---------|-------------|
| `{entity}.index` | `users.index` | List resources |
| `{entity}.show` | `users.show` | View single resource |
| `{entity}.store` | `users.store` | Create resource |
| `{entity}.update` | `users.update` | Update resource |
| `{entity}.destroy` | `users.destroy` | Delete resource |

### Default Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| `god` | Super admin | All permissions |
| `admin` | Administrator | Most permissions |
| `tech` | Technical user | Read-only access |
| `customer` | Customer user | Limited access |

---

## React Component Example

```tsx
import { useState, useEffect } from 'react';
import { permissionManagerService } from './services/permission-manager.service';

interface Role {
  id: string;
  name: string;
  description: string | null;
  permissions: string[];
}

interface PermissionGroup {
  module: string;
  permissions: { id: string; name: string; checked: boolean }[];
}

export function RolePermissionManager({ roleId }: { roleId: string }) {
  const [role, setRole] = useState<Role | null>(null);
  const [permissionGroups, setPermissionGroups] = useState<PermissionGroup[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, [roleId]);

  async function loadData() {
    const [roleData, permissionsData] = await Promise.all([
      permissionManagerService.getRole(roleId),
      permissionManagerService.listPermissions({ perPage: 500 }),
    ]);

    const rolePermissionIds = roleData.relationships?.permissions?.data.map(p => p.id) || [];

    setRole({
      id: roleData.id,
      name: roleData.attributes.name,
      description: roleData.attributes.description,
      permissions: rolePermissionIds,
    });

    const grouped = permissionManagerService.groupPermissionsByModule(permissionsData.data);
    const groups = Object.entries(grouped).map(([module, perms]) => ({
      module,
      permissions: perms.map(p => ({
        id: p.id,
        name: p.attributes.name,
        checked: rolePermissionIds.includes(p.id),
      })),
    }));

    setPermissionGroups(groups.sort((a, b) => a.module.localeCompare(b.module)));
    setLoading(false);
  }

  function handleToggle(permissionId: string) {
    setPermissionGroups(groups =>
      groups.map(group => ({
        ...group,
        permissions: group.permissions.map(p =>
          p.id === permissionId ? { ...p, checked: !p.checked } : p
        ),
      }))
    );
  }

  async function handleSave() {
    const selectedIds = permissionGroups
      .flatMap(g => g.permissions)
      .filter(p => p.checked)
      .map(p => p.id);

    await permissionManagerService.updateRolePermissions(roleId, selectedIds);
    alert('Permissions saved!');
  }

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <h2>Edit Role: {role?.name}</h2>
      <p>{role?.description}</p>

      {permissionGroups.map(group => (
        <div key={group.module} className="permission-group">
          <h3>{group.module}</h3>
          {group.permissions.map(perm => (
            <label key={perm.id}>
              <input
                type="checkbox"
                checked={perm.checked}
                onChange={() => handleToggle(perm.id)}
              />
              {perm.name}
            </label>
          ))}
        </div>
      ))}

      <button onClick={handleSave}>Save Permissions</button>
    </div>
  );
}
```

---

## Validation Rules

### Role

| Field | Rules |
|-------|-------|
| name | required, string, unique:roles,name |
| description | nullable, string |
| guard_name | required, in:api |
| permissions | valid JSON:API toMany relationship |

### Permission

| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| guard_name | required, string |

---

## Error Responses

### Duplicate Role Name (422)

```json
{
  "errors": [
    {
      "status": "422",
      "source": { "pointer": "/data/attributes/name" },
      "title": "Unprocessable Entity",
      "detail": "The name has already been taken."
    }
  ]
}
```
