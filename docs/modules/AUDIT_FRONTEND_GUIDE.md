# Audit Module - Frontend Integration Guide

**Module:** Audit
**Entities:** 1 (Audit)
**Endpoints:** 2 (Index, Show - Read-only)
**Base Path:** `/api/v1`

## Overview

The Audit module provides read-only access to activity logs captured by Spatie Activity Log. All model changes across the application are automatically tracked and available for review.

**Note:** This module is read-only. Audit records are created automatically when models are modified.

## Core Entity

### Audit

**Endpoint:** `/audits`
**Resource Type:** `audits`

#### TypeScript Interface

```typescript
type AuditEvent = 'created' | 'updated' | 'deleted';

interface Audit {
  id: string;
  event: AuditEvent;
  userId: number | null;           // causer_id
  auditableType: string;           // e.g., 'Modules\\User\\Models\\User'
  auditableId: number;             // subject_id
  oldValues: Record<string, any> | null;
  newValues: Record<string, any> | null;
  ipAddress: string | null;
  userAgent: string | null;
  createdAt: string;
  updatedAt: string;
}

// Formatted for display
interface AuditDisplay {
  id: string;
  event: AuditEvent;
  userName: string;
  entityType: string;
  entityId: number;
  changes: FieldChange[];
  timestamp: string;
  ipAddress: string | null;
}

interface FieldChange {
  field: string;
  oldValue: any;
  newValue: any;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `event` | `event` | string | Yes | Yes |
| `userId` | `causer_id` | number | Yes | Yes (as `causer`) |
| `auditableType` | `subject_type` | string | Yes | Yes |
| `auditableId` | `subject_id` | number | Yes | Yes |
| `oldValues` | `properties->old` | object | No | No |
| `newValues` | `properties->attributes` | object | No | No |
| `ipAddress` | `properties->ip_address` | string | No | No |
| `userAgent` | `properties->user_agent` | string | No | No |
| `createdAt` | `created_at` | datetime | Yes | No |
| `updatedAt` | `updated_at` | datetime | Yes | No |

---

## API Endpoints

### List Audits

```http
GET /api/v1/audits
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `filter[causer]` | `1` | Filter by user ID |
| `filter[event]` | `updated` | Filter by event type |
| `filter[auditableType]` | `Modules\\User\\Models\\User` | Filter by entity type |
| `filter[auditableId]` | `5` | Filter by entity ID |
| `sort` | `-createdAt` | Sort by field (- for desc) |
| `page[number]` | `1` | Page number |
| `page[size]` | `15` | Items per page |

#### Example: Get all changes by a specific user

```http
GET /api/v1/audits?filter[causer]=1&sort=-createdAt
```

#### Example: Get all changes to a specific entity

```http
GET /api/v1/audits?filter[auditableType]=Modules%5CUser%5CModels%5CUser&filter[auditableId]=5
```

#### Response

```json
{
  "data": [
    {
      "type": "audits",
      "id": "123",
      "attributes": {
        "event": "updated",
        "userId": 1,
        "auditableType": "Modules\\User\\Models\\User",
        "auditableId": 5,
        "oldValues": "{\"name\":\"John\",\"status\":\"active\"}",
        "newValues": "{\"name\":\"John Doe\",\"status\":\"inactive\"}",
        "ipAddress": "192.168.1.100",
        "userAgent": "Mozilla/5.0...",
        "createdAt": "2024-01-15T10:30:00Z",
        "updatedAt": "2024-01-15T10:30:00Z"
      }
    }
  ],
  "meta": {
    "page": {
      "currentPage": 1,
      "perPage": 15,
      "total": 1250
    }
  }
}
```

---

### Get Single Audit

```http
GET /api/v1/audits/{id}
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

---

## TypeScript Service

```typescript
interface AuditResource {
  type: 'audits';
  id: string;
  attributes: {
    event: 'created' | 'updated' | 'deleted';
    userId: number | null;
    auditableType: string;
    auditableId: number;
    oldValues: string | null;
    newValues: string | null;
    ipAddress: string | null;
    userAgent: string | null;
    createdAt: string;
    updatedAt: string;
  };
}

interface AuditFilters {
  causer?: number;
  event?: 'created' | 'updated' | 'deleted';
  auditableType?: string;
  auditableId?: number;
}

interface ParsedAudit {
  id: string;
  event: string;
  userId: number | null;
  entityType: string;
  entityId: number;
  oldValues: Record<string, any> | null;
  newValues: Record<string, any> | null;
  changes: { field: string; oldValue: any; newValue: any }[];
  ipAddress: string | null;
  userAgent: string | null;
  createdAt: Date;
}

class AuditService {
  private baseUrl = '/api/v1/audits';

  async list(params?: {
    filters?: AuditFilters;
    sort?: string;
    page?: number;
    perPage?: number;
  }): Promise<{ data: AuditResource[]; meta: any }> {
    const queryParams = new URLSearchParams();

    if (params?.filters?.causer) {
      queryParams.set('filter[causer]', params.filters.causer.toString());
    }
    if (params?.filters?.event) {
      queryParams.set('filter[event]', params.filters.event);
    }
    if (params?.filters?.auditableType) {
      queryParams.set('filter[auditableType]', params.filters.auditableType);
    }
    if (params?.filters?.auditableId) {
      queryParams.set('filter[auditableId]', params.filters.auditableId.toString());
    }
    if (params?.sort) {
      queryParams.set('sort', params.sort);
    }
    if (params?.page) {
      queryParams.set('page[number]', params.page.toString());
    }
    if (params?.perPage) {
      queryParams.set('page[size]', params.perPage.toString());
    }

    const response = await fetch(`${this.baseUrl}?${queryParams.toString()}`, {
      headers: this.getHeaders(),
    });
    return response.json();
  }

  async get(id: string): Promise<AuditResource> {
    const response = await fetch(`${this.baseUrl}/${id}`, {
      headers: this.getHeaders(),
    });
    const result = await response.json();
    return result.data;
  }

  /**
   * Get audit history for a specific entity
   */
  async getEntityHistory(entityType: string, entityId: number): Promise<ParsedAudit[]> {
    const result = await this.list({
      filters: { auditableType: entityType, auditableId: entityId },
      sort: '-createdAt',
      perPage: 100,
    });

    return result.data.map(audit => this.parseAudit(audit));
  }

  /**
   * Get recent activity by a user
   */
  async getUserActivity(userId: number, limit = 50): Promise<ParsedAudit[]> {
    const result = await this.list({
      filters: { causer: userId },
      sort: '-createdAt',
      perPage: limit,
    });

    return result.data.map(audit => this.parseAudit(audit));
  }

  /**
   * Parse raw audit resource into usable format
   */
  parseAudit(audit: AuditResource): ParsedAudit {
    const oldValues = audit.attributes.oldValues
      ? JSON.parse(audit.attributes.oldValues)
      : null;
    const newValues = audit.attributes.newValues
      ? JSON.parse(audit.attributes.newValues)
      : null;

    // Calculate field changes
    const changes: { field: string; oldValue: any; newValue: any }[] = [];

    if (oldValues && newValues) {
      const allFields = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);
      for (const field of allFields) {
        if (JSON.stringify(oldValues[field]) !== JSON.stringify(newValues[field])) {
          changes.push({
            field,
            oldValue: oldValues[field],
            newValue: newValues[field],
          });
        }
      }
    } else if (newValues) {
      // Created event - all fields are new
      for (const [field, value] of Object.entries(newValues)) {
        changes.push({ field, oldValue: null, newValue: value });
      }
    }

    return {
      id: audit.id,
      event: audit.attributes.event,
      userId: audit.attributes.userId,
      entityType: this.formatEntityType(audit.attributes.auditableType),
      entityId: audit.attributes.auditableId,
      oldValues,
      newValues,
      changes,
      ipAddress: audit.attributes.ipAddress,
      userAgent: audit.attributes.userAgent,
      createdAt: new Date(audit.attributes.createdAt),
    };
  }

  /**
   * Format entity type for display
   * "Modules\\User\\Models\\User" -> "User"
   */
  formatEntityType(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1];
  }

  private getHeaders(): Record<string, string> {
    return {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Accept': 'application/vnd.api+json',
    };
  }
}

export const auditService = new AuditService();
```

---

## React Components

### Audit Log Viewer

```tsx
import { useState, useEffect } from 'react';
import { auditService, ParsedAudit } from './services/audit.service';

export function AuditLogViewer() {
  const [audits, setAudits] = useState<ParsedAudit[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    loadAudits();
  }, [page]);

  async function loadAudits() {
    setLoading(true);
    try {
      const result = await auditService.list({
        sort: '-createdAt',
        page,
        perPage: 20,
      });
      setAudits(result.data.map(a => auditService.parseAudit(a)));
      setTotalPages(Math.ceil(result.meta.page.total / 20));
    } finally {
      setLoading(false);
    }
  }

  const getEventColor = (event: string) => {
    switch (event) {
      case 'created': return 'green';
      case 'updated': return 'blue';
      case 'deleted': return 'red';
      default: return 'gray';
    }
  };

  if (loading) return <div>Loading audit logs...</div>;

  return (
    <div className="audit-log">
      <h2>Activity Log</h2>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Event</th>
            <th>Entity</th>
            <th>Changes</th>
            <th>User</th>
          </tr>
        </thead>
        <tbody>
          {audits.map(audit => (
            <tr key={audit.id}>
              <td>{audit.createdAt.toLocaleString()}</td>
              <td>
                <span style={{ color: getEventColor(audit.event) }}>
                  {audit.event}
                </span>
              </td>
              <td>{audit.entityType} #{audit.entityId}</td>
              <td>
                {audit.changes.slice(0, 2).map((change, i) => (
                  <div key={i}>
                    <strong>{change.field}:</strong>{' '}
                    {String(change.oldValue)} → {String(change.newValue)}
                  </div>
                ))}
                {audit.changes.length > 2 && (
                  <span>+{audit.changes.length - 2} more</span>
                )}
              </td>
              <td>User #{audit.userId}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <div className="pagination">
        <button disabled={page === 1} onClick={() => setPage(p => p - 1)}>
          Previous
        </button>
        <span>Page {page} of {totalPages}</span>
        <button disabled={page === totalPages} onClick={() => setPage(p => p + 1)}>
          Next
        </button>
      </div>
    </div>
  );
}
```

### Entity History Component

```tsx
import { useState, useEffect } from 'react';
import { auditService, ParsedAudit } from './services/audit.service';

interface EntityHistoryProps {
  entityType: string;  // Full type: 'Modules\\User\\Models\\User'
  entityId: number;
}

export function EntityHistory({ entityType, entityId }: EntityHistoryProps) {
  const [history, setHistory] = useState<ParsedAudit[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadHistory();
  }, [entityType, entityId]);

  async function loadHistory() {
    setLoading(true);
    try {
      const audits = await auditService.getEntityHistory(entityType, entityId);
      setHistory(audits);
    } finally {
      setLoading(false);
    }
  }

  if (loading) return <div>Loading history...</div>;

  if (history.length === 0) {
    return <div>No change history available.</div>;
  }

  return (
    <div className="entity-history">
      <h3>Change History</h3>

      <div className="timeline">
        {history.map(audit => (
          <div key={audit.id} className="timeline-item">
            <div className="timeline-header">
              <span className={`event-badge ${audit.event}`}>
                {audit.event}
              </span>
              <span className="timestamp">
                {audit.createdAt.toLocaleString()}
              </span>
              {audit.userId && (
                <span className="user">by User #{audit.userId}</span>
              )}
            </div>

            {audit.changes.length > 0 && (
              <table className="changes-table">
                <thead>
                  <tr>
                    <th>Field</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                  </tr>
                </thead>
                <tbody>
                  {audit.changes.map((change, i) => (
                    <tr key={i}>
                      <td>{change.field}</td>
                      <td className="old-value">
                        {formatValue(change.oldValue)}
                      </td>
                      <td className="new-value">
                        {formatValue(change.newValue)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

function formatValue(value: any): string {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `audits.index` | List audit logs | god, admin |
| `audits.show` | View audit details | god, admin |

---

## Common Entity Types

| Module | Entity Type |
|--------|-------------|
| User | `Modules\User\Models\User` |
| Product | `Modules\Product\Models\Product` |
| Inventory | `Modules\Inventory\Models\InventoryMovement` |
| Sales | `Modules\Sales\Models\SalesOrder` |
| Purchase | `Modules\Purchase\Models\PurchaseOrder` |
| Finance | `Modules\Finance\Models\ARInvoice` |
| HR | `Modules\HR\Models\Employee` |

**Note:** When filtering by `auditableType`, you must URL-encode the backslashes:
- `Modules\User\Models\User` → `Modules%5CUser%5CModels%5CUser`
