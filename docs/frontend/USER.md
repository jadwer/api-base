# User Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| User | `/api/v1/users` | System users |

## User

```typescript
interface User {
  id: string;
  name: string;
  email: string;
  emailVerifiedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

// List users (admin only)
GET /api/v1/users

// Get user
GET /api/v1/users/{id}

// Create user (admin only)
POST /api/v1/users
{
  "data": {
    "type": "users",
    "attributes": {
      "name": "Juan Perez",
      "email": "juan.perez@company.com",
      "password": "securepassword123"
    }
  }
}

// Update user
PATCH /api/v1/users/{id}
{
  "data": {
    "type": "users",
    "id": "1",
    "attributes": {
      "name": "Juan Carlos Perez"
    }
  }
}

// Delete user (admin only)
DELETE /api/v1/users/{id}
```

## User Roles

Users have roles assigned via Spatie Permission:

| Role | Description | Access |
|------|-------------|--------|
| god | Super administrator | Everything |
| admin | Administrator | Full module access |
| tech | Technical user | Read-only |
| customer | Customer | Own resources only |

```typescript
// Get user with roles
GET /api/v1/users/{id}?include=roles

// Response includes roles in relationships
{
  "data": {
    "type": "users",
    "id": "1",
    "attributes": { "name": "Admin User" },
    "relationships": {
      "roles": {
        "data": [{ "type": "roles", "id": "2" }]
      }
    }
  },
  "included": [
    { "type": "roles", "id": "2", "attributes": { "name": "admin" } }
  ]
}
```

## Password Management

```typescript
// Change own password
POST /api/v1/users/{id}/change-password
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}

// Reset password (admin only)
POST /api/v1/users/{id}/reset-password
{
  "password": "newpassword"
}
```

## User-Contact Link

Users can be linked to Contacts (for customer portal):

```typescript
// Customer user linked to contact
GET /api/v1/users/{id}?include=contact

// When customer logs in, they only see their own:
// - Sales orders (contact_id matches)
// - AR Invoices (contact_id matches)
// - Shipments (via sales orders)
```

## Filters

| Filter | Example |
|--------|---------|
| `filter[name]` | `?filter[name]=Juan` |
| `filter[email]` | `?filter[email]=juan@company.com` |

## Permissions

| Action | god | admin | tech | customer |
|--------|-----|-------|------|----------|
| List users | Yes | Yes | No | No |
| View user | Yes | Yes | Own | Own |
| Create user | Yes | Yes | No | No |
| Update user | Yes | Yes | No | Own |
| Delete user | Yes | Yes | No | No |
| Assign roles | Yes | Yes | No | No |
