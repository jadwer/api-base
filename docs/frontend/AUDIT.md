# Audit Module

## Overview

Activity logging and audit trails for all system entities using Spatie Activity Log.

## Entity

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Audit | `/api/v1/audits` | Activity log entries |

## Audit Log Entry

```typescript
interface Audit {
  id: string;
  logName: string;           // 'default'
  description: string;       // 'created', 'updated', 'deleted'
  subjectType: string;       // Model class
  subjectId: number;
  causerType: string | null; // Usually 'App\\Models\\User'
  causerId: number | null;   // User who performed action
  properties: {
    old: Record<string, any> | null;  // Previous values
    attributes: Record<string, any>;   // New values
  };
  event: string;             // 'created', 'updated', 'deleted'
  batchUuid: string | null;  // For grouped actions
  createdAt: string;
}

// List all audit logs
GET /api/v1/audits?sort=-createdAt&page[size]=50

// Filter by entity type
GET /api/v1/audits?filter[subject_type]=SalesOrder

// Filter by user
GET /api/v1/audits?filter[causer_id]=5

// Filter by event
GET /api/v1/audits?filter[event]=deleted

// Filter by date range
GET /api/v1/audits?filter[created_at][gte]=2026-01-01&filter[created_at][lte]=2026-01-31
```

## Audited Entities (37 Models)

| Module | Entities |
|--------|----------|
| **Product** | Product, Category, Brand, Unit, ProductVariant |
| **Inventory** | Warehouse, WarehouseLocation, Stock, InventoryMovement, ProductBatch, CycleCount |
| **Contacts** | Contact, ContactAddress, ContactPerson, ContactDocument |
| **Sales** | SalesOrder, SalesOrderItem, Shipment, Backorder, DiscountRule |
| **Purchase** | PurchaseOrder, PurchaseOrderItem, Budget, BudgetAllocation |
| **Finance** | ARInvoice, APInvoice, ARPayment, APPayment, BankAccount, BankTransaction |
| **Accounting** | Account, JournalEntry, JournalLine, FiscalPeriod |
| **HR** | Employee, Department, Position, Attendance, Leave, PayrollItem |
| **CRM** | Lead, Opportunity, Campaign, Activity |
| **Billing** | CfdiInvoice, CfdiItem, CompanySetting |

## Viewing Changes

```typescript
// Get audit with changes
GET /api/v1/audits/{id}

// Response
{
  "data": {
    "type": "audits",
    "id": "1",
    "attributes": {
      "description": "updated",
      "subjectType": "Modules\\Sales\\Models\\SalesOrder",
      "subjectId": 15,
      "causerId": 5,
      "event": "updated",
      "properties": {
        "old": {
          "status": "draft",
          "totalAmount": 1000.00
        },
        "attributes": {
          "status": "confirmed",
          "totalAmount": 1500.00
        }
      },
      "createdAt": "2026-01-08T10:30:00Z"
    }
  }
}
```

## Entity History

```typescript
// Get history for specific entity
GET /api/v1/audits?filter[subject_type]=SalesOrder&filter[subject_id]=15&sort=-createdAt

// Response shows all changes to SalesOrder #15
{
  "data": [
    {
      "attributes": {
        "event": "updated",
        "properties": {
          "old": { "status": "confirmed" },
          "attributes": { "status": "processing" }
        },
        "createdAt": "2026-01-08T14:00:00Z"
      }
    },
    {
      "attributes": {
        "event": "updated",
        "properties": {
          "old": { "status": "draft" },
          "attributes": { "status": "confirmed" }
        },
        "createdAt": "2026-01-08T10:30:00Z"
      }
    },
    {
      "attributes": {
        "event": "created",
        "properties": {
          "attributes": { "status": "draft", "totalAmount": 1000.00 }
        },
        "createdAt": "2026-01-08T09:00:00Z"
      }
    }
  ]
}
```

## User Activity

```typescript
// Get all actions by a user
GET /api/v1/audits?filter[causer_id]=5&sort=-createdAt

// Get user's actions today
GET /api/v1/audits?filter[causer_id]=5&filter[created_at][gte]=2026-01-08

// Summary of user actions
GET /api/v1/audits/user-summary?user_id=5&start_date=2026-01-01&end_date=2026-01-31

// Response
{
  "user": { "id": 5, "name": "Juan Perez" },
  "period": { "start": "2026-01-01", "end": "2026-01-31" },
  "summary": {
    "created": 45,
    "updated": 120,
    "deleted": 5,
    "total": 170
  },
  "byEntity": [
    { "entity": "SalesOrder", "created": 20, "updated": 50, "deleted": 0 },
    { "entity": "Contact", "created": 10, "updated": 30, "deleted": 2 }
  ]
}
```

## Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[subject_type]` | `SalesOrder` | Filter by entity type |
| `filter[subject_id]` | `15` | Filter by entity ID |
| `filter[causer_id]` | `5` | Filter by user |
| `filter[event]` | `created,updated,deleted` | Filter by event type |
| `filter[created_at][gte]` | `2026-01-01` | From date |
| `filter[created_at][lte]` | `2026-01-31` | To date |
| `filter[log_name]` | `default` | Filter by log name |

## Audit Trail Component

```typescript
// React component for showing entity history
async function getEntityHistory(entityType: string, entityId: number) {
  const response = await fetch(
    `/api/v1/audits?filter[subject_type]=${entityType}&filter[subject_id]=${entityId}&sort=-createdAt`,
    { headers }
  );
  return response.json();
}

function AuditTrail({ entityType, entityId }: Props) {
  const [history, setHistory] = useState([]);

  useEffect(() => {
    getEntityHistory(entityType, entityId).then(res => setHistory(res.data));
  }, [entityType, entityId]);

  return (
    <div className="audit-trail">
      <h3>History</h3>
      {history.map(entry => (
        <div key={entry.id} className="audit-entry">
          <span className="event">{entry.attributes.event}</span>
          <span className="user">by User #{entry.attributes.causerId}</span>
          <span className="date">{entry.attributes.createdAt}</span>

          {entry.attributes.event === 'updated' && (
            <div className="changes">
              {Object.keys(entry.attributes.properties.old || {}).map(key => (
                <div key={key}>
                  <strong>{key}:</strong>
                  <span className="old">{entry.attributes.properties.old[key]}</span>
                  <span className="arrow">-></span>
                  <span className="new">{entry.attributes.properties.attributes[key]}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
```

## Permissions

| Permission | Description |
|------------|-------------|
| `audits.index` | View audit logs |
| `audits.show` | View specific audit entry |

Only admin and god roles can view audit logs by default.

## Data Retention

- Audit logs are retained for 7-15 years (SAT Mexico requirement)
- Logs include cryptographic hash for tamper detection
- No deletion of audit entries allowed

## Excluded Fields

The following fields are NOT logged to reduce noise:

- `created_at`
- `updated_at`
- `remember_token`
- `password`
- Encrypted fields
