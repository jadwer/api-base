# Permission Manager Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Role | `/api/v1/roles` | User roles |
| Permission | `/api/v1/permissions` | Granular permissions |

## Role

```typescript
interface Role {
  id: string;
  name: string;
  guardName: string;        // 'web' or 'api'
  createdAt: string;
  updatedAt: string;
}

// List roles
GET /api/v1/roles

// Get role with permissions
GET /api/v1/roles/{id}?include=permissions

// Create role
POST /api/v1/roles
{
  "data": {
    "type": "roles",
    "attributes": {
      "name": "sales_manager",
      "guardName": "api"
    }
  }
}

// Update role
PATCH /api/v1/roles/{id}

// Delete role
DELETE /api/v1/roles/{id}
```

## Permission

```typescript
interface Permission {
  id: string;
  name: string;             // 'sales.sales-orders.store'
  guardName: string;
  createdAt: string;
  updatedAt: string;
}

// List permissions
GET /api/v1/permissions

// Permission naming convention:
// module.entity.action
// Examples:
// - sales.sales-orders.index
// - sales.sales-orders.show
// - sales.sales-orders.store
// - sales.sales-orders.update
// - sales.sales-orders.destroy
```

## Default Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| god | Super admin | All permissions |
| admin | Administrator | All except system config |
| tech | Technical | Read-only (*.index, *.show) |
| customer | Customer | Own resources only |

## Assign Role to User

```typescript
// Assign role
POST /api/v1/users/{userId}/assign-role
{
  "role": "sales_manager"
}

// Remove role
POST /api/v1/users/{userId}/remove-role
{
  "role": "sales_manager"
}

// Sync roles (replace all)
POST /api/v1/users/{userId}/sync-roles
{
  "roles": ["admin", "sales_manager"]
}
```

## Assign Permission to Role

```typescript
// Give permission to role
POST /api/v1/roles/{roleId}/give-permission
{
  "permission": "sales.sales-orders.store"
}

// Revoke permission
POST /api/v1/roles/{roleId}/revoke-permission
{
  "permission": "sales.sales-orders.store"
}

// Sync permissions (replace all)
POST /api/v1/roles/{roleId}/sync-permissions
{
  "permissions": [
    "sales.sales-orders.index",
    "sales.sales-orders.show",
    "sales.sales-orders.store"
  ]
}
```

## Check Permissions (Frontend)

```typescript
// Current user permissions are returned on login
POST /api/auth/login
Response: {
  "token": "...",
  "user": {
    "id": 1,
    "name": "Admin",
    "roles": ["admin"],
    "permissions": [
      "sales.sales-orders.index",
      "sales.sales-orders.show",
      // ... all permissions
    ]
  }
}

// Frontend helper
function hasPermission(permission: string): boolean {
  return currentUser.permissions.includes(permission);
}

function hasRole(role: string): boolean {
  return currentUser.roles.includes(role);
}

// Usage
if (hasPermission('sales.sales-orders.store')) {
  showCreateButton();
}
```

## Permission Matrix by Module

### Sales Module
| Permission | god | admin | tech | customer |
|------------|-----|-------|------|----------|
| sales.sales-orders.index | Yes | Yes | Yes | Own |
| sales.sales-orders.show | Yes | Yes | Yes | Own |
| sales.sales-orders.store | Yes | Yes | No | Yes* |
| sales.sales-orders.update | Yes | Yes | No | No |
| sales.sales-orders.destroy | Yes | Yes | No | No |

*Customer can create orders through ecommerce

### Finance Module
| Permission | god | admin | tech | customer |
|------------|-----|-------|------|----------|
| finance.ar-invoices.index | Yes | Yes | Yes | Own |
| finance.ar-invoices.show | Yes | Yes | Yes | Own |
| finance.ar-invoices.store | Yes | Yes | No | No |
| finance.ar-invoices.update | Yes | Yes | No | No |
| finance.ar-invoices.destroy | Yes | Yes | No | No |

### Accounting Module
| Permission | god | admin | tech | customer |
|------------|-----|-------|------|----------|
| accounting.journal-entries.* | Yes | Yes | No | No |
| accounting.accounts.* | Yes | Yes | Read | No |
| accounting.fiscal-periods.close | Yes | Yes | No | No |

## UI Implementation

```typescript
// Role-based menu filtering
const menuItems = [
  {
    label: 'Sales Orders',
    path: '/sales-orders',
    permission: 'sales.sales-orders.index'
  },
  {
    label: 'Create Order',
    path: '/sales-orders/new',
    permission: 'sales.sales-orders.store'
  },
  {
    label: 'Journal Entries',
    path: '/journal-entries',
    permission: 'accounting.journal-entries.index'
  }
];

// Filter by permission
const visibleMenuItems = menuItems.filter(item =>
  hasPermission(item.permission)
);

// Button visibility
<Button
  onClick={createOrder}
  disabled={!hasPermission('sales.sales-orders.store')}
>
  Create Order
</Button>
```

## Permissions by Action

| Action | Permission Pattern |
|--------|-------------------|
| List | `module.entity.index` |
| View | `module.entity.show` |
| Create | `module.entity.store` |
| Update | `module.entity.update` |
| Delete | `module.entity.destroy` |
