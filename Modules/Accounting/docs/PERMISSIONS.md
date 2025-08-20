# 🔐 Permissions - Accounting

**Generated:** 2025-08-20 11:02:18

## Available Permissions

| Permission | Description |
|------------|-------------|
| `accounting.accounts.index` | List all accounts |
| `accounting.accounts.show` | View specific accounts |
| `accounting.accounts.store` | Create new accounts |
| `accounting.accounts.update` | Update existing accounts |
| `accounting.accounts.destroy` | Delete accounts |
| `accounting.fiscal-periods.index` | List all fiscal-periods |
| `accounting.fiscal-periods.show` | View specific fiscal-periods |
| `accounting.fiscal-periods.store` | Create new fiscal-periods |
| `accounting.fiscal-periods.update` | Update existing fiscal-periods |
| `accounting.fiscal-periods.destroy` | Delete fiscal-periods |
| `accounting.journals.index` | List all journals |
| `accounting.journals.show` | View specific journals |
| `accounting.journals.store` | Create new journals |
| `accounting.journals.update` | Update existing journals |
| `accounting.journals.destroy` | Delete journals |
| `accounting.journal-entries.index` | List all journal-entries |
| `accounting.journal-entries.show` | View specific journal-entries |
| `accounting.journal-entries.store` | Create new journal-entries |
| `accounting.journal-entries.update` | Update existing journal-entries |
| `accounting.journal-entries.destroy` | Delete journal-entries |
| `accounting.journal-lines.index` | List all journal-lines |
| `accounting.journal-lines.show` | View specific journal-lines |
| `accounting.journal-lines.store` | Create new journal-lines |
| `accounting.journal-lines.update` | Update existing journal-lines |
| `accounting.journal-lines.destroy` | Delete journal-lines |
| `accounting.exchange-rates.index` | List all exchange-rates |
| `accounting.exchange-rates.show` | View specific exchange-rates |
| `accounting.exchange-rates.store` | Create new exchange-rates |
| `accounting.exchange-rates.update` | Update existing exchange-rates |
| `accounting.exchange-rates.destroy` | Delete exchange-rates |

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
