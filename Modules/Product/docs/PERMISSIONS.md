# 🔐 Permissions - Product

**Generated:** 2025-08-19 17:59:33

## Default Role Assignments

| Role | Permissions | Description |
|------|-------------|-------------|
| `admin` | All permissions | Full access to all operations |
| `tech` | index, show | Read-only access |
| `customer` | Limited | Restricted access based on business rules |

## Usage

```php
// Check permission in controller
if ($request->user()->can('module.resource.action')) {
    // Perform action
}

// In Authorizer
public function index(Request $request): bool
{
    return $request->user()?->can('module.resource.index') ?? false;
}
```
