# 🔐 Permissions - Finance

**Generated:** 2025-08-20 17:33:38

## Available Permissions

| Permission | Description |
|------------|-------------|
| `finance.bank-accounts.index` | List all bank-accounts |
| `finance.bank-accounts.show` | View specific bank-accounts |
| `finance.bank-accounts.store` | Create new bank-accounts |
| `finance.bank-accounts.update` | Update existing bank-accounts |
| `finance.bank-accounts.destroy` | Delete bank-accounts |
| `finance.bank-statements.index` | List all bank-statements |
| `finance.bank-statements.show` | View specific bank-statements |
| `finance.bank-statements.store` | Create new bank-statements |
| `finance.bank-statements.update` | Update existing bank-statements |
| `finance.bank-statements.destroy` | Delete bank-statements |
| `finance.bank-statement-lines.index` | List all bank-statement-lines |
| `finance.bank-statement-lines.show` | View specific bank-statement-lines |
| `finance.bank-statement-lines.store` | Create new bank-statement-lines |
| `finance.bank-statement-lines.update` | Update existing bank-statement-lines |
| `finance.bank-statement-lines.destroy` | Delete bank-statement-lines |
| `finance.ap-invoices.index` | List all ap-invoices |
| `finance.ap-invoices.show` | View specific ap-invoices |
| `finance.ap-invoices.store` | Create new ap-invoices |
| `finance.ap-invoices.update` | Update existing ap-invoices |
| `finance.ap-invoices.destroy` | Delete ap-invoices |
| `finance.ap-invoice-lines.index` | List all ap-invoice-lines |
| `finance.ap-invoice-lines.show` | View specific ap-invoice-lines |
| `finance.ap-invoice-lines.store` | Create new ap-invoice-lines |
| `finance.ap-invoice-lines.update` | Update existing ap-invoice-lines |
| `finance.ap-invoice-lines.destroy` | Delete ap-invoice-lines |
| `finance.ap-payments.index` | List all ap-payments |
| `finance.ap-payments.show` | View specific ap-payments |
| `finance.ap-payments.store` | Create new ap-payments |
| `finance.ap-payments.update` | Update existing ap-payments |
| `finance.ap-payments.destroy` | Delete ap-payments |
| `finance.ap-invoice-payments.index` | List all ap-invoice-payments |
| `finance.ap-invoice-payments.show` | View specific ap-invoice-payments |
| `finance.ap-invoice-payments.store` | Create new ap-invoice-payments |
| `finance.ap-invoice-payments.update` | Update existing ap-invoice-payments |
| `finance.ap-invoice-payments.destroy` | Delete ap-invoice-payments |
| `finance.ar-invoices.index` | List all ar-invoices |
| `finance.ar-invoices.show` | View specific ar-invoices |
| `finance.ar-invoices.store` | Create new ar-invoices |
| `finance.ar-invoices.update` | Update existing ar-invoices |
| `finance.ar-invoices.destroy` | Delete ar-invoices |
| `finance.ar-invoice-lines.index` | List all ar-invoice-lines |
| `finance.ar-invoice-lines.show` | View specific ar-invoice-lines |
| `finance.ar-invoice-lines.store` | Create new ar-invoice-lines |
| `finance.ar-invoice-lines.update` | Update existing ar-invoice-lines |
| `finance.ar-invoice-lines.destroy` | Delete ar-invoice-lines |
| `finance.ar-receipts.index` | List all ar-receipts |
| `finance.ar-receipts.show` | View specific ar-receipts |
| `finance.ar-receipts.store` | Create new ar-receipts |
| `finance.ar-receipts.update` | Update existing ar-receipts |
| `finance.ar-receipts.destroy` | Delete ar-receipts |
| `finance.ar-invoice-receipts.index` | List all ar-invoice-receipts |
| `finance.ar-invoice-receipts.show` | View specific ar-invoice-receipts |
| `finance.ar-invoice-receipts.store` | Create new ar-invoice-receipts |
| `finance.ar-invoice-receipts.update` | Update existing ar-invoice-receipts |
| `finance.ar-invoice-receipts.destroy` | Delete ar-invoice-receipts |

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
