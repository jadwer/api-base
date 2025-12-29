# Audit Module - Frontend Integration Guide

**Module:** Audit
**Entities:** 1 (Audit)
**Endpoints:** 2 (Index, Show - Read-only)
**Base Path:** `/api/v1`
**Last Updated:** 2025-12-29
**Models with Audit:** 37 of 74 (50%)

## Overview

The Audit module provides read-only access to activity logs captured by Spatie Activity Log. All model changes across the application are automatically tracked and available for review.

**Note:** This module is read-only. Audit records are created automatically when models are modified.

### What's Tracked

| Category | Models |
|----------|--------|
| **Financial** | SalesOrder, PurchaseOrder, APInvoice, ARInvoice, Payment, PaymentApplication, CFDIInvoice, JournalEntry, PaymentTransaction |
| **Master Data** | Product, Contact, Employee, Lead, Opportunity, Campaign |
| **Supporting** | Brand, Category, Warehouse, Department, Position, BankAccount, Account |
| **Operational** | Stock, Attendance, Activity, CartItem, CheckoutSession, ShoppingCart, Wishlist, ProductReview |
| **Auth Events** | login, logout, login_failed |

## Core Entity

### Audit

**Endpoint:** `/audits`
**Resource Type:** `audits`

#### TypeScript Interface

```typescript
type AuditEvent = 'created' | 'updated' | 'deleted' | 'login' | 'logout' | 'login_failed';

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
| `filter[event]` | `updated` | Filter by event type (created, updated, deleted, login, logout, login_failed) |
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
| `audit.index` | List audit logs | god, admin, auditor |
| `audit.show` | View audit details | god, admin, auditor |
| `audit.export` | Export audit logs | god, admin, auditor |

### Auditor Role

The `auditor` role provides read-only access specifically for audit and compliance purposes:

**Audit Permissions:**
- `audit.index` - View audit log list
- `audit.show` - View audit log details
- `audit.export` - Export audit data

**System Health Permissions:**
- `system-health.index` - View system health status
- `system-health.database` - View database metrics
- `system-health.storage` - View storage metrics
- `system-health.queue` - View queue status
- `system-health.errors` - View error logs
- `system-health.metrics` - View application metrics

This role is ideal for:
- Internal auditors
- Compliance officers
- External audit consultants (with time-limited access)
- Security monitoring personnel

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

---

## Next.js App Router Integration

### API Client (`lib/api/audit.ts`)

```typescript
import { getAuthToken } from '@/lib/auth';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export type AuditEvent = 'created' | 'updated' | 'deleted' | 'login' | 'logout' | 'login_failed';

export interface AuditRecord {
  id: string;
  type: 'audits';
  attributes: {
    event: AuditEvent;
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

export interface AuditFilters {
  causer?: number;
  event?: AuditEvent;
  auditableType?: string;
  auditableId?: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    page: {
      currentPage: number;
      perPage: number;
      total: number;
      lastPage: number;
    };
  };
}

export async function fetchAudits(params?: {
  filters?: AuditFilters;
  sort?: string;
  page?: number;
  perPage?: number;
}): Promise<PaginatedResponse<AuditRecord>> {
  const searchParams = new URLSearchParams();

  if (params?.filters?.causer) {
    searchParams.set('filter[causer]', params.filters.causer.toString());
  }
  if (params?.filters?.event) {
    searchParams.set('filter[event]', params.filters.event);
  }
  if (params?.filters?.auditableType) {
    searchParams.set('filter[auditableType]', params.filters.auditableType);
  }
  if (params?.filters?.auditableId) {
    searchParams.set('filter[auditableId]', params.filters.auditableId.toString());
  }
  if (params?.sort) {
    searchParams.set('sort', params.sort);
  }
  if (params?.page) {
    searchParams.set('page[number]', params.page.toString());
  }
  if (params?.perPage) {
    searchParams.set('page[size]', params.perPage.toString());
  }

  const token = getAuthToken();
  const response = await fetch(`${API_BASE}/audits?${searchParams.toString()}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/vnd.api+json',
    },
    next: { revalidate: 0 }, // Always fresh data
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch audits: ${response.status}`);
  }

  return response.json();
}

export async function fetchAuditById(id: string): Promise<AuditRecord> {
  const token = getAuthToken();
  const response = await fetch(`${API_BASE}/audits/${id}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/vnd.api+json',
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch audit: ${response.status}`);
  }

  const result = await response.json();
  return result.data;
}

// Helper to parse entity type for display
export function formatEntityType(type: string): string {
  const parts = type.split('\\');
  return parts[parts.length - 1];
}

// Helper to parse changes
export interface ParsedChange {
  field: string;
  oldValue: unknown;
  newValue: unknown;
}

export function parseAuditChanges(audit: AuditRecord): ParsedChange[] {
  const { oldValues, newValues } = audit.attributes;
  const changes: ParsedChange[] = [];

  const old = oldValues ? JSON.parse(oldValues) : {};
  const updated = newValues ? JSON.parse(newValues) : {};

  const allFields = new Set([...Object.keys(old), ...Object.keys(updated)]);

  for (const field of allFields) {
    if (JSON.stringify(old[field]) !== JSON.stringify(updated[field])) {
      changes.push({
        field,
        oldValue: old[field] ?? null,
        newValue: updated[field] ?? null,
      });
    }
  }

  return changes;
}
```

### Server Component - Audit List Page (`app/admin/audit/page.tsx`)

```tsx
import { fetchAudits, formatEntityType, parseAuditChanges } from '@/lib/api/audit';
import { AuditFilters } from './audit-filters';
import { Pagination } from '@/components/ui/pagination';

interface PageProps {
  searchParams: {
    page?: string;
    event?: string;
    causer?: string;
  };
}

export default async function AuditPage({ searchParams }: PageProps) {
  const page = parseInt(searchParams.page || '1');
  const event = searchParams.event as any;
  const causer = searchParams.causer ? parseInt(searchParams.causer) : undefined;

  const { data: audits, meta } = await fetchAudits({
    filters: { event, causer },
    sort: '-createdAt',
    page,
    perPage: 20,
  });

  return (
    <div className="container mx-auto py-6">
      <h1 className="text-2xl font-bold mb-6">Registro de Auditoría</h1>

      <AuditFilters />

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Fecha
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Evento
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Entidad
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Cambios
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Usuario
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {audits.map((audit) => {
              const changes = parseAuditChanges(audit);
              return (
                <tr key={audit.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(audit.attributes.createdAt).toLocaleString('es-MX')}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <EventBadge event={audit.attributes.event} />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    <span className="font-medium">
                      {formatEntityType(audit.attributes.auditableType)}
                    </span>
                    <span className="text-gray-500 ml-1">
                      #{audit.attributes.auditableId}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-500">
                    {changes.slice(0, 2).map((change, i) => (
                      <div key={i} className="truncate max-w-xs">
                        <span className="font-medium">{change.field}:</span>{' '}
                        {String(change.oldValue ?? '—')} → {String(change.newValue ?? '—')}
                      </div>
                    ))}
                    {changes.length > 2 && (
                      <span className="text-gray-400">+{changes.length - 2} más</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {audit.attributes.userId ? `#${audit.attributes.userId}` : '—'}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <Pagination
        currentPage={meta.page.currentPage}
        totalPages={meta.page.lastPage}
        baseUrl="/admin/audit"
      />
    </div>
  );
}

function EventBadge({ event }: { event: string }) {
  const styles: Record<string, string> = {
    created: 'bg-green-100 text-green-800',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
    login: 'bg-purple-100 text-purple-800',
    logout: 'bg-gray-100 text-gray-800',
    login_failed: 'bg-orange-100 text-orange-800',
  };

  const labels: Record<string, string> = {
    created: 'Creado',
    updated: 'Actualizado',
    deleted: 'Eliminado',
    login: 'Login',
    logout: 'Logout',
    login_failed: 'Login Fallido',
  };

  return (
    <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[event] || 'bg-gray-100'}`}>
      {labels[event] || event}
    </span>
  );
}
```

### Client Component - Entity History (`components/audit/entity-history.tsx`)

```tsx
'use client';

import { useEffect, useState } from 'react';
import { fetchAudits, AuditRecord, parseAuditChanges, formatEntityType } from '@/lib/api/audit';

interface EntityHistoryProps {
  entityType: string;  // e.g., 'Modules\\Product\\Models\\Product'
  entityId: number;
}

export function EntityHistory({ entityType, entityId }: EntityHistoryProps) {
  const [audits, setAudits] = useState<AuditRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function load() {
      try {
        setLoading(true);
        const { data } = await fetchAudits({
          filters: { auditableType: entityType, auditableId: entityId },
          sort: '-createdAt',
          perPage: 50,
        });
        setAudits(data);
      } catch (err) {
        setError('Error al cargar historial');
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [entityType, entityId]);

  if (loading) {
    return <div className="animate-pulse">Cargando historial...</div>;
  }

  if (error) {
    return <div className="text-red-500">{error}</div>;
  }

  if (audits.length === 0) {
    return <div className="text-gray-500">Sin historial de cambios.</div>;
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Historial de Cambios</h3>

      <div className="space-y-3">
        {audits.map((audit) => {
          const changes = parseAuditChanges(audit);
          return (
            <div key={audit.id} className="border rounded-lg p-4 bg-gray-50">
              <div className="flex items-center justify-between mb-2">
                <span className={`text-sm font-medium ${getEventColor(audit.attributes.event)}`}>
                  {audit.attributes.event.toUpperCase()}
                </span>
                <span className="text-sm text-gray-500">
                  {new Date(audit.attributes.createdAt).toLocaleString('es-MX')}
                </span>
              </div>

              {changes.length > 0 && (
                <div className="text-sm space-y-1">
                  {changes.map((change, i) => (
                    <div key={i} className="flex gap-2">
                      <span className="font-medium text-gray-700">{change.field}:</span>
                      <span className="text-red-600 line-through">
                        {formatValue(change.oldValue)}
                      </span>
                      <span>→</span>
                      <span className="text-green-600">
                        {formatValue(change.newValue)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              {audit.attributes.userId && (
                <div className="mt-2 text-xs text-gray-400">
                  Por usuario #{audit.attributes.userId}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

function getEventColor(event: string): string {
  switch (event) {
    case 'created': return 'text-green-600';
    case 'updated': return 'text-blue-600';
    case 'deleted': return 'text-red-600';
    default: return 'text-gray-600';
  }
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'object') return JSON.stringify(value);
  if (typeof value === 'boolean') return value ? 'Sí' : 'No';
  return String(value);
}
```

### Usage Example - Product Detail Page

```tsx
// app/products/[id]/page.tsx
import { EntityHistory } from '@/components/audit/entity-history';

export default async function ProductPage({ params }: { params: { id: string } }) {
  // ... fetch product data ...

  return (
    <div>
      {/* Product details */}

      {/* History tab/section */}
      <EntityHistory
        entityType="Modules\\Product\\Models\\Product"
        entityId={parseInt(params.id)}
      />
    </div>
  );
}
```

---

## User Activity Reports (Required Feature)

El sistema **debe** incluir reportes de actividad por usuario para:
1. **Seguridad** - Ver quién hizo qué y cuándo
2. **Auditoría** - Cumplimiento de normativas
3. **Administración** - Monitoreo de actividad del equipo

### User Activity Dashboard Component (`components/audit/user-activity-report.tsx`)

```tsx
'use client';

import { useEffect, useState } from 'react';
import { fetchAudits, AuditRecord, formatEntityType, AuditEvent } from '@/lib/api/audit';

interface UserActivityReportProps {
  userId: number;
  userName?: string;
}

interface ActivitySummary {
  totalActions: number;
  creates: number;
  updates: number;
  deletes: number;
  logins: number;
  lastActivity: Date | null;
  mostActiveEntity: string | null;
}

export function UserActivityReport({ userId, userName }: UserActivityReportProps) {
  const [audits, setAudits] = useState<AuditRecord[]>([]);
  const [summary, setSummary] = useState<ActivitySummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [dateRange, setDateRange] = useState<'today' | 'week' | 'month' | 'all'>('week');

  useEffect(() => {
    loadActivity();
  }, [userId, dateRange]);

  async function loadActivity() {
    setLoading(true);
    try {
      const { data } = await fetchAudits({
        filters: { causer: userId },
        sort: '-createdAt',
        perPage: 100,
      });

      setAudits(data);
      setSummary(calculateSummary(data));
    } finally {
      setLoading(false);
    }
  }

  function calculateSummary(data: AuditRecord[]): ActivitySummary {
    const entityCounts: Record<string, number> = {};

    let creates = 0, updates = 0, deletes = 0, logins = 0;

    for (const audit of data) {
      const event = audit.attributes.event;
      if (event === 'created') creates++;
      else if (event === 'updated') updates++;
      else if (event === 'deleted') deletes++;
      else if (event === 'login') logins++;

      const entity = formatEntityType(audit.attributes.auditableType);
      entityCounts[entity] = (entityCounts[entity] || 0) + 1;
    }

    const mostActiveEntity = Object.entries(entityCounts)
      .sort(([, a], [, b]) => b - a)[0]?.[0] || null;

    return {
      totalActions: data.length,
      creates,
      updates,
      deletes,
      logins,
      lastActivity: data[0] ? new Date(data[0].attributes.createdAt) : null,
      mostActiveEntity,
    };
  }

  if (loading) {
    return <div className="animate-pulse p-4">Cargando actividad...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold">
          Actividad de {userName || `Usuario #${userId}`}
        </h2>
        <select
          value={dateRange}
          onChange={(e) => setDateRange(e.target.value as any)}
          className="border rounded px-3 py-1"
        >
          <option value="today">Hoy</option>
          <option value="week">Última semana</option>
          <option value="month">Último mes</option>
          <option value="all">Todo</option>
        </select>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <StatCard
            label="Total acciones"
            value={summary.totalActions}
            color="blue"
          />
          <StatCard
            label="Creaciones"
            value={summary.creates}
            color="green"
          />
          <StatCard
            label="Actualizaciones"
            value={summary.updates}
            color="yellow"
          />
          <StatCard
            label="Eliminaciones"
            value={summary.deletes}
            color="red"
          />
        </div>
      )}

      {/* Login History */}
      <div className="bg-white rounded-lg shadow p-4">
        <h3 className="font-semibold mb-3">Historial de Sesiones</h3>
        <LoginHistory userId={userId} />
      </div>

      {/* Recent Activity Timeline */}
      <div className="bg-white rounded-lg shadow p-4">
        <h3 className="font-semibold mb-3">Actividad Reciente</h3>
        <div className="space-y-2 max-h-96 overflow-y-auto">
          {audits.slice(0, 50).map((audit) => (
            <ActivityItem key={audit.id} audit={audit} />
          ))}
        </div>
      </div>
    </div>
  );
}

function StatCard({ label, value, color }: { label: string; value: number; color: string }) {
  const colors: Record<string, string> = {
    blue: 'bg-blue-50 text-blue-700 border-blue-200',
    green: 'bg-green-50 text-green-700 border-green-200',
    yellow: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    red: 'bg-red-50 text-red-700 border-red-200',
  };

  return (
    <div className={`p-4 rounded-lg border ${colors[color]}`}>
      <div className="text-2xl font-bold">{value}</div>
      <div className="text-sm">{label}</div>
    </div>
  );
}

function ActivityItem({ audit }: { audit: AuditRecord }) {
  const eventLabels: Record<string, string> = {
    created: 'creó',
    updated: 'actualizó',
    deleted: 'eliminó',
    login: 'inició sesión',
    logout: 'cerró sesión',
    login_failed: 'falló login',
  };

  const entity = formatEntityType(audit.attributes.auditableType);
  const action = eventLabels[audit.attributes.event] || audit.attributes.event;

  return (
    <div className="flex items-center gap-3 py-2 border-b last:border-0">
      <EventDot event={audit.attributes.event} />
      <div className="flex-1">
        <span className="font-medium">{action}</span>
        {audit.attributes.auditableId > 0 && (
          <span className="text-gray-600"> {entity} #{audit.attributes.auditableId}</span>
        )}
      </div>
      <div className="text-sm text-gray-500">
        {new Date(audit.attributes.createdAt).toLocaleString('es-MX', {
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })}
      </div>
    </div>
  );
}

function EventDot({ event }: { event: string }) {
  const colors: Record<string, string> = {
    created: 'bg-green-500',
    updated: 'bg-blue-500',
    deleted: 'bg-red-500',
    login: 'bg-purple-500',
    logout: 'bg-gray-400',
    login_failed: 'bg-orange-500',
  };

  return (
    <div className={`w-2 h-2 rounded-full ${colors[event] || 'bg-gray-400'}`} />
  );
}

function LoginHistory({ userId }: { userId: number }) {
  const [logins, setLogins] = useState<AuditRecord[]>([]);

  useEffect(() => {
    async function load() {
      const { data } = await fetchAudits({
        filters: { causer: userId, event: 'login' },
        sort: '-createdAt',
        perPage: 10,
      });
      setLogins(data);
    }
    load();
  }, [userId]);

  if (logins.length === 0) {
    return <p className="text-gray-500">Sin registros de login.</p>;
  }

  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-gray-500">
          <th className="pb-2">Fecha</th>
          <th className="pb-2">IP</th>
          <th className="pb-2">Navegador</th>
        </tr>
      </thead>
      <tbody>
        {logins.map((login) => (
          <tr key={login.id} className="border-t">
            <td className="py-2">
              {new Date(login.attributes.createdAt).toLocaleString('es-MX')}
            </td>
            <td className="py-2">{login.attributes.ipAddress || '—'}</td>
            <td className="py-2 truncate max-w-xs">
              {parseUserAgent(login.attributes.userAgent)}
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function parseUserAgent(ua: string | null): string {
  if (!ua) return '—';
  // Simple parsing - in production use a proper UA parser
  if (ua.includes('Chrome')) return 'Chrome';
  if (ua.includes('Firefox')) return 'Firefox';
  if (ua.includes('Safari')) return 'Safari';
  if (ua.includes('Edge')) return 'Edge';
  return 'Otro';
}
```

### Admin User List with Activity (`app/admin/users/page.tsx`)

```tsx
import { fetchUsers } from '@/lib/api/users';
import { fetchAudits } from '@/lib/api/audit';
import Link from 'next/link';

export default async function UsersPage() {
  const { data: users } = await fetchUsers();

  return (
    <div className="container mx-auto py-6">
      <h1 className="text-2xl font-bold mb-6">Usuarios</h1>

      <table className="min-w-full bg-white rounded-lg shadow">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left">Usuario</th>
            <th className="px-6 py-3 text-left">Email</th>
            <th className="px-6 py-3 text-left">Rol</th>
            <th className="px-6 py-3 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id} className="border-t hover:bg-gray-50">
              <td className="px-6 py-4">{user.attributes.name}</td>
              <td className="px-6 py-4">{user.attributes.email}</td>
              <td className="px-6 py-4">
                {/* Role badge */}
              </td>
              <td className="px-6 py-4">
                <Link
                  href={`/admin/users/${user.id}/activity`}
                  className="text-blue-600 hover:underline"
                >
                  Ver Actividad
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

### User Activity Page (`app/admin/users/[id]/activity/page.tsx`)

```tsx
import { UserActivityReport } from '@/components/audit/user-activity-report';
import { fetchUserById } from '@/lib/api/users';

interface PageProps {
  params: { id: string };
}

export default async function UserActivityPage({ params }: PageProps) {
  const user = await fetchUserById(params.id);

  return (
    <div className="container mx-auto py-6">
      <UserActivityReport
        userId={parseInt(params.id)}
        userName={user.attributes.name}
      />
    </div>
  );
}
```

### Required API Queries for Reports

```typescript
// 1. Get all activity for a specific user
GET /api/v1/audits?filter[causer]={userId}&sort=-createdAt&page[size]=100

// 2. Get login history for a user
GET /api/v1/audits?filter[causer]={userId}&filter[event]=login&sort=-createdAt

// 3. Get failed login attempts (security monitoring)
GET /api/v1/audits?filter[event]=login_failed&sort=-createdAt

// 4. Get all changes to a specific entity
GET /api/v1/audits?filter[auditableType]=Modules%5CProduct%5CModels%5CProduct&filter[auditableId]=5

// 5. Get recent activity across all users (admin dashboard)
GET /api/v1/audits?sort=-createdAt&page[size]=50
```

---

### Login History Hook (`hooks/use-login-history.ts`)

```typescript
'use client';

import { useEffect, useState } from 'react';
import { fetchAudits, AuditRecord } from '@/lib/api/audit';

export function useLoginHistory(userId?: number) {
  const [history, setHistory] = useState<AuditRecord[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const { data } = await fetchAudits({
          filters: {
            event: 'login' as any,
            ...(userId && { causer: userId }),
          },
          sort: '-createdAt',
          perPage: 20,
        });
        setHistory(data);
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [userId]);

  return { history, loading };
}
```

---

## Entity Type Reference (Complete List)

| Module | Entity | Full Type |
|--------|--------|-----------|
| **Product** | Product | `Modules\Product\Models\Product` |
| **Product** | Brand | `Modules\Product\Models\Brand` |
| **Product** | Category | `Modules\Product\Models\Category` |
| **Inventory** | Warehouse | `Modules\Inventory\Models\Warehouse` |
| **Inventory** | Stock | `Modules\Inventory\Models\Stock` |
| **Inventory** | InventoryMovement | `Modules\Inventory\Models\InventoryMovement` |
| **Sales** | SalesOrder | `Modules\Sales\Models\SalesOrder` |
| **Sales** | SalesOrderItem | `Modules\Sales\Models\SalesOrderItem` |
| **Purchase** | PurchaseOrder | `Modules\Purchase\Models\PurchaseOrder` |
| **Purchase** | PurchaseOrderItem | `Modules\Purchase\Models\PurchaseOrderItem` |
| **Finance** | APInvoice | `Modules\Finance\Models\APInvoice` |
| **Finance** | ARInvoice | `Modules\Finance\Models\ARInvoice` |
| **Finance** | Payment | `Modules\Finance\Models\Payment` |
| **Finance** | PaymentApplication | `Modules\Finance\Models\PaymentApplication` |
| **Finance** | BankAccount | `Modules\Finance\Models\BankAccount` |
| **Accounting** | Account | `Modules\Accounting\Models\Account` |
| **Accounting** | JournalEntry | `Modules\Accounting\Models\JournalEntry` |
| **Billing** | CFDIInvoice | `Modules\Billing\Models\CFDIInvoice` |
| **Billing** | PaymentTransaction | `Modules\Billing\Models\PaymentTransaction` |
| **HR** | Employee | `Modules\HR\Models\Employee` |
| **HR** | Department | `Modules\HR\Models\Department` |
| **HR** | Position | `Modules\HR\Models\Position` |
| **HR** | Attendance | `Modules\HR\Models\Attendance` |
| **CRM** | Lead | `Modules\CRM\Models\Lead` |
| **CRM** | Opportunity | `Modules\CRM\Models\Opportunity` |
| **CRM** | Campaign | `Modules\CRM\Models\Campaign` |
| **CRM** | Activity | `Modules\CRM\Models\Activity` |
| **Contacts** | Contact | `Modules\Contacts\Models\Contact` |
| **Ecommerce** | CartItem | `Modules\Ecommerce\Models\CartItem` |
| **Ecommerce** | CheckoutSession | `Modules\Ecommerce\Models\CheckoutSession` |
| **Ecommerce** | ShoppingCart | `Modules\Ecommerce\Models\ShoppingCart` |
| **Ecommerce** | Wishlist | `Modules\Ecommerce\Models\Wishlist` |
| **Ecommerce** | ProductReview | `Modules\Ecommerce\Models\ProductReview` |
| **User** | User | `Modules\User\Models\User` |
| **PageBuilder** | Page | `Modules\PageBuilder\Models\Page` |
