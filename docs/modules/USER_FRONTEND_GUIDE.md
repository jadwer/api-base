# User Module - Frontend Integration Guide

**Module:** User
**Entities:** 1 (User)
**Endpoints:** 5 CRUD
**Base Path:** `/api/v1`

## Overview

The User module manages user accounts with full CRUD operations. Integrates with PermissionManager for role-based access control. Uses JSON:API specification.

## Core Entity

### User

**Endpoint:** `/users`
**Resource Type:** `users`

#### TypeScript Interface

```typescript
type UserStatus = 'active' | 'inactive' | 'banned';

interface User {
  id: string;
  name: string;
  email: string;
  status: UserStatus;
  role: string;  // First role name (read-only, computed)
  emailVerifiedAt: string | null;
  createdAt: string;
  updatedAt: string;
  deletedAt: string | null;
}

interface UserCreateRequest {
  name: string;
  email: string;
  password: string;
  status?: UserStatus;  // defaults to 'active'
  roles?: { type: 'roles'; id: string }[];
}

interface UserUpdateRequest {
  name?: string;
  email?: string;
  password?: string;  // nullable on update
  status?: UserStatus;
  roles?: { type: 'roles'; id: string }[];
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Notes |
|---------------|-----------------|------|----------|----------|-------|
| `name` | `name` | string | Yes | Yes | max 255 chars |
| `email` | `email` | string | Yes | Yes | unique, max 255 |
| `password` | `password` | string | Create: Yes, Update: No | No | min 8 chars, hidden |
| `status` | `status` | string | Yes | No | active, inactive, banned |
| `role` | - | string | No | No | Read-only computed |
| `emailVerifiedAt` | `email_verified_at` | datetime | No | No | Read-only |
| `createdAt` | `created_at` | datetime | No | Yes | Read-only |
| `updatedAt` | `updated_at` | datetime | No | No | Read-only |
| `deletedAt` | `deleted_at` | datetime | No | No | Read-only |

#### Relationships

- `roles` → Role[] (belongsToMany)

---

## API Endpoints

### List Users

```http
GET /api/v1/users
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `include` | `roles` | Include related roles |
| `sort` | `name`, `-created_at` | Sort by field (- for desc) |
| `page[number]` | `1` | Page number |
| `page[size]` | `15` | Items per page |

#### Response

```json
{
  "data": [
    {
      "type": "users",
      "id": "1",
      "attributes": {
        "name": "Admin User",
        "email": "admin@example.com",
        "status": "active",
        "role": "admin",
        "emailVerifiedAt": null,
        "createdAt": "2024-01-01T00:00:00Z",
        "updatedAt": "2024-01-01T00:00:00Z",
        "deletedAt": null
      },
      "relationships": {
        "roles": {
          "data": [
            { "type": "roles", "id": "2" }
          ]
        }
      }
    }
  ],
  "included": [
    {
      "type": "roles",
      "id": "2",
      "attributes": {
        "name": "admin",
        "description": "Administrator role"
      }
    }
  ],
  "meta": {
    "page": {
      "currentPage": 1,
      "perPage": 15,
      "total": 10
    }
  }
}
```

---

### Get User

```http
GET /api/v1/users/{id}
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

---

### Create User

```http
POST /api/v1/users
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

#### Request Body

```json
{
  "data": {
    "type": "users",
    "attributes": {
      "name": "New User",
      "email": "newuser@example.com",
      "password": "securepassword123",
      "status": "active"
    },
    "relationships": {
      "roles": {
        "data": [
          { "type": "roles", "id": "4" }
        ]
      }
    }
  }
}
```

---

### Update User

```http
PATCH /api/v1/users/{id}
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

#### Request Body

```json
{
  "data": {
    "type": "users",
    "id": "5",
    "attributes": {
      "name": "Updated Name",
      "status": "inactive"
    }
  }
}
```

**Note:** Password is optional on update. Only include if changing password.

---

### Delete User

```http
DELETE /api/v1/users/{id}
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

Returns `204 No Content` on success.

---

## TypeScript Service

```typescript
import { JsonApiClient } from './json-api-client';

interface UserResource {
  type: 'users';
  id: string;
  attributes: {
    name: string;
    email: string;
    status: 'active' | 'inactive' | 'banned';
    role: string;
    emailVerifiedAt: string | null;
    createdAt: string;
    updatedAt: string;
  };
  relationships?: {
    roles?: {
      data: { type: 'roles'; id: string }[];
    };
  };
}

interface CreateUserData {
  name: string;
  email: string;
  password: string;
  status?: 'active' | 'inactive' | 'banned';
  roleIds?: string[];
}

interface UpdateUserData {
  name?: string;
  email?: string;
  password?: string;
  status?: 'active' | 'inactive' | 'banned';
  roleIds?: string[];
}

class UserService {
  private client: JsonApiClient;

  constructor(client: JsonApiClient) {
    this.client = client;
  }

  async list(params?: {
    include?: string[];
    sort?: string;
    page?: number;
    perPage?: number;
  }): Promise<{ data: UserResource[]; meta: any }> {
    return this.client.get('/users', {
      include: params?.include?.join(','),
      sort: params?.sort,
      'page[number]': params?.page,
      'page[size]': params?.perPage,
    });
  }

  async get(id: string, include?: string[]): Promise<UserResource> {
    return this.client.get(`/users/${id}`, {
      include: include?.join(','),
    });
  }

  async create(data: CreateUserData): Promise<UserResource> {
    const payload = {
      data: {
        type: 'users',
        attributes: {
          name: data.name,
          email: data.email,
          password: data.password,
          status: data.status || 'active',
        },
        relationships: data.roleIds ? {
          roles: {
            data: data.roleIds.map(id => ({ type: 'roles', id })),
          },
        } : undefined,
      },
    };
    return this.client.post('/users', payload);
  }

  async update(id: string, data: UpdateUserData): Promise<UserResource> {
    const attributes: any = {};
    if (data.name !== undefined) attributes.name = data.name;
    if (data.email !== undefined) attributes.email = data.email;
    if (data.password !== undefined) attributes.password = data.password;
    if (data.status !== undefined) attributes.status = data.status;

    const payload: any = {
      data: {
        type: 'users',
        id,
        attributes,
      },
    };

    if (data.roleIds) {
      payload.data.relationships = {
        roles: {
          data: data.roleIds.map(id => ({ type: 'roles', id })),
        },
      };
    }

    return this.client.patch(`/users/${id}`, payload);
  }

  async delete(id: string): Promise<void> {
    return this.client.delete(`/users/${id}`);
  }

  async assignRoles(userId: string, roleIds: string[]): Promise<void> {
    return this.client.patch(`/users/${userId}/relationships/roles`, {
      data: roleIds.map(id => ({ type: 'roles', id })),
    });
  }
}

export const userService = new UserService(jsonApiClient);
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `users.index` | List users | god, admin |
| `users.show` | View user details | god, admin |
| `users.store` | Create users | god, admin |
| `users.update` | Update users | god, admin |
| `users.destroy` | Delete users | god, admin |

---

## Validation Rules

### Create User

| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| email | required, email, max:255, unique:users,email |
| password | required, string, min:8 |
| status | required, in:active,inactive,banned |
| roles | valid JSON:API toMany relationship |

### Update User

| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| email | required, email, max:255, unique:users,email (ignores current) |
| password | nullable, string, min:8 |
| status | required, in:active,inactive,banned |
| roles | valid JSON:API toMany relationship |

---

## Error Responses

### Validation Error (422)

```json
{
  "errors": [
    {
      "status": "422",
      "source": { "pointer": "/data/attributes/email" },
      "title": "Unprocessable Entity",
      "detail": "The email has already been taken."
    }
  ]
}
```

### Forbidden (403)

```json
{
  "errors": [
    {
      "status": "403",
      "title": "Forbidden",
      "detail": "This action is unauthorized."
    }
  ]
}
```

---

## React Component Example

```tsx
import { useState, useEffect } from 'react';
import { userService } from './services/user.service';

interface User {
  id: string;
  name: string;
  email: string;
  status: string;
  role: string;
}

export function UserList() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUsers();
  }, []);

  async function loadUsers() {
    try {
      const response = await userService.list({ include: ['roles'] });
      setUsers(response.data.map(u => ({
        id: u.id,
        name: u.attributes.name,
        email: u.attributes.email,
        status: u.attributes.status,
        role: u.attributes.role,
      })));
    } finally {
      setLoading(false);
    }
  }

  async function handleDelete(id: string) {
    if (confirm('Are you sure?')) {
      await userService.delete(id);
      loadUsers();
    }
  }

  if (loading) return <div>Loading...</div>;

  return (
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {users.map(user => (
          <tr key={user.id}>
            <td>{user.name}</td>
            <td>{user.email}</td>
            <td>{user.role}</td>
            <td>{user.status}</td>
            <td>
              <button onClick={() => handleDelete(user.id)}>Delete</button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```
