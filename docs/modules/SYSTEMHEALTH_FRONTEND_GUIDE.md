# SystemHealth Frontend Integration Guide

**Module:** SystemHealth
**Created:** 2025-12-29
**Purpose:** System monitoring, health checks, and metrics collection

## Overview

The SystemHealth module provides API endpoints for monitoring system status, database metrics, storage usage, queue health, and error tracking. It integrates with Laravel Telescope for error visibility.

## API Endpoints

### Base URL
```
/api/v1/system-health
```

### Available Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| GET | `/ping` | No | - | Simple health check for uptime monitoring |
| GET | `/` | Yes | `system-health.index` | Complete system health status |
| GET | `/database` | Yes | `system-health.database` | Database connection and metrics |
| GET | `/storage` | Yes | `system-health.storage` | Disk space usage |
| GET | `/queue` | Yes | `system-health.queue` | Queue and failed jobs status |
| GET | `/errors` | Yes | `system-health.errors` | Recent errors from Telescope |
| GET | `/metrics` | Yes | `system-health.metrics` | Application metrics (record counts) |

---

## Endpoint Details

### 1. Ping (Public - No Auth)

Simple endpoint for uptime monitoring services (UptimeRobot, Pingdom, etc.).

```http
GET /api/v1/system-health/ping
```

**Response:**
```json
{
  "data": {
    "type": "health-check",
    "id": "ping",
    "attributes": {
      "status": "ok",
      "timestamp": "2025-12-29T10:36:19+00:00"
    }
  }
}
```

**Status Values:**
- `ok` - Database is responding
- `error` - Database connection failed

---

### 2. Full Health Status

Complete system health overview with all checks and metrics.

```http
GET /api/v1/system-health
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "type": "system-health",
    "id": "current",
    "attributes": {
      "status": "healthy",
      "timestamp": "2025-12-29T10:37:04+00:00",
      "environment": "local",
      "checks": {
        "database": {
          "status": "healthy",
          "message": "Database connection successful",
          "responseTimeMs": 0.71
        },
        "cache": {
          "status": "healthy",
          "message": "Cache is working",
          "driver": "array",
          "responseTimeMs": 0.29
        },
        "queue": {
          "status": "healthy",
          "message": "Queue is healthy",
          "driver": "database",
          "pendingJobs": 69,
          "failedJobs": 0
        },
        "storage": {
          "status": "healthy",
          "message": "Disk usage: 6.79%",
          "totalGb": 1006.85,
          "usedGb": 68.36,
          "freeGb": 938.49,
          "usedPercent": 6.79
        }
      },
      "metrics": {
        "database": { ... },
        "application": { ... },
        "errors": { ... }
      }
    }
  }
}
```

**Overall Status Values:**
- `healthy` - All systems operational
- `warning` - Some systems degraded
- `critical` - Critical systems failing

---

### 3. Database Metrics

Database connection status and table statistics.

```http
GET /api/v1/system-health/database
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "type": "database-health",
    "id": "current",
    "attributes": {
      "check": {
        "status": "healthy",
        "message": "Database connection successful",
        "responseTimeMs": 0.71
      },
      "metrics": {
        "driver": "mysql",
        "database": "api-base",
        "totalSizeMb": "11.59",
        "topTables": [
          {
            "name": "telescope_entries",
            "rows": 5269,
            "sizeMb": 4.5
          },
          {
            "name": "activity_log",
            "rows": 285,
            "sizeMb": 0.31
          }
        ]
      }
    }
  }
}
```

---

### 4. Storage Metrics

Disk space usage for the storage directory.

```http
GET /api/v1/system-health/storage
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "healthy",
  "message": "Disk usage: 6.79%",
  "totalGb": 1006.85,
  "usedGb": 68.36,
  "freeGb": 938.49,
  "usedPercent": 6.79
}
```

**Status Thresholds:**
- `healthy` - Usage < 85%
- `warning` - Usage 85-95%
- `critical` - Usage >= 95%

---

### 5. Queue Status

Queue driver status and job counts.

```http
GET /api/v1/system-health/queue
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "healthy",
  "message": "Queue is healthy",
  "driver": "database",
  "pendingJobs": 69,
  "failedJobs": 0
}
```

**Status Thresholds:**
- `healthy` - No failed jobs
- `warning` - 1-10 failed jobs
- `critical` - > 10 failed jobs

---

### 6. Error Logs

Recent exceptions and error counts from Telescope.

```http
GET /api/v1/system-health/errors
Authorization: Bearer {token}
```

**Response:**
```json
{
  "recentExceptions": [
    {
      "id": "a0b547a4-405c-458d-9966-0f5a40af666c",
      "timestamp": "2025-12-29 10:35:14",
      "class": "Illuminate\\Contracts\\Container\\BindingResolutionException",
      "message": "Target class [MyClass] does not exist.",
      "file": "/path/to/Container.php",
      "line": 1163
    }
  ],
  "last24hCounts": {
    "exception": 6,
    "query": 54,
    "request": 1,
    "command": 2396
  },
  "totalExceptionsLast24h": 6
}
```

---

### 7. Application Metrics

Record counts for key application tables.

```http
GET /api/v1/system-health/metrics
Authorization: Bearer {token}
```

**Response:**
```json
{
  "users": 34,
  "products": 57,
  "salesOrders": 17,
  "purchaseOrders": 3,
  "contacts": 30,
  "activityLast24h": 0,
  "totalActivityLogs": 285
}
```

---

## Next.js Integration

### API Client Service

```typescript
// lib/api/system-health.ts
import { getAuthToken } from '@/lib/auth';

const API_URL = process.env.NEXT_PUBLIC_API_URL;

export interface HealthStatus {
  status: 'healthy' | 'warning' | 'critical';
  timestamp: string;
  environment: string;
  checks: {
    database: HealthCheck;
    cache: HealthCheck;
    queue: QueueCheck;
    storage: StorageCheck;
  };
  metrics: {
    database: DatabaseMetrics;
    application: ApplicationMetrics;
    errors: ErrorMetrics;
  };
}

export interface HealthCheck {
  status: 'healthy' | 'warning' | 'critical';
  message: string;
  responseTimeMs?: number;
  driver?: string;
  error?: string;
}

export interface QueueCheck extends HealthCheck {
  pendingJobs: number;
  failedJobs: number;
}

export interface StorageCheck extends HealthCheck {
  totalGb: number;
  usedGb: number;
  freeGb: number;
  usedPercent: number;
}

export interface DatabaseMetrics {
  driver: string;
  database: string;
  totalSizeMb: string;
  topTables: TableInfo[];
}

export interface TableInfo {
  name: string;
  rows: number;
  sizeMb: number;
}

export interface ApplicationMetrics {
  users: number;
  products: number;
  salesOrders: number;
  purchaseOrders: number;
  contacts: number;
  activityLast24h: number;
  totalActivityLogs: number;
}

export interface ErrorMetrics {
  recentExceptions: ExceptionInfo[];
  last24hCounts: Record<string, number>;
  totalExceptionsLast24h: number;
}

export interface ExceptionInfo {
  id: string;
  timestamp: string;
  class: string;
  message: string | null;
  file: string | null;
  line: number | null;
}

// Public ping endpoint (no auth)
export async function pingHealth(): Promise<{ status: string; timestamp: string }> {
  const response = await fetch(`${API_URL}/api/v1/system-health/ping`);
  return response.json();
}

// Full health status (requires auth)
export async function getHealthStatus(): Promise<HealthStatus> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to fetch health status');
  }

  return response.json();
}

// Individual endpoints
export async function getDatabaseHealth(): Promise<{ connection: HealthCheck; metrics: DatabaseMetrics }> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health/database`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  return response.json();
}

export async function getStorageHealth(): Promise<StorageCheck> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health/storage`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  return response.json();
}

export async function getQueueHealth(): Promise<QueueCheck> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health/queue`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  return response.json();
}

export async function getErrorLogs(): Promise<ErrorMetrics> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health/errors`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  return response.json();
}

export async function getApplicationMetrics(): Promise<ApplicationMetrics> {
  const token = getAuthToken();
  const response = await fetch(`${API_URL}/api/v1/system-health/metrics`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  return response.json();
}
```

### React Hook for Auto-Refresh

```typescript
// hooks/useSystemHealth.ts
import { useState, useEffect, useCallback } from 'react';
import { getHealthStatus, HealthStatus } from '@/lib/api/system-health';

export function useSystemHealth(refreshInterval = 30000) {
  const [health, setHealth] = useState<HealthStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchHealth = useCallback(async () => {
    try {
      const data = await getHealthStatus();
      setHealth(data);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error'));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchHealth();

    const interval = setInterval(fetchHealth, refreshInterval);
    return () => clearInterval(interval);
  }, [fetchHealth, refreshInterval]);

  return { health, loading, error, refresh: fetchHealth };
}
```

### Dashboard Component Example

```tsx
// components/SystemHealthDashboard.tsx
'use client';

import { useSystemHealth } from '@/hooks/useSystemHealth';

const StatusBadge = ({ status }: { status: string }) => {
  const colors = {
    healthy: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    critical: 'bg-red-100 text-red-800',
    ok: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
  };

  return (
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status as keyof typeof colors] || 'bg-gray-100'}`}>
      {status.toUpperCase()}
    </span>
  );
};

export function SystemHealthDashboard() {
  const { health, loading, error, refresh } = useSystemHealth(30000);

  if (loading) {
    return <div className="animate-pulse">Loading system health...</div>;
  }

  if (error) {
    return (
      <div className="text-red-600">
        Error loading health status: {error.message}
        <button onClick={refresh} className="ml-2 underline">Retry</button>
      </div>
    );
  }

  if (!health) return null;

  return (
    <div className="space-y-6">
      {/* Overall Status */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold">System Health</h2>
        <div className="flex items-center gap-4">
          <StatusBadge status={health.status} />
          <span className="text-sm text-gray-500">
            Last updated: {new Date(health.timestamp).toLocaleTimeString()}
          </span>
          <button onClick={refresh} className="text-blue-600 hover:underline text-sm">
            Refresh
          </button>
        </div>
      </div>

      {/* Health Checks Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Database Check */}
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex justify-between items-start">
            <h3 className="font-semibold">Database</h3>
            <StatusBadge status={health.checks.database.status} />
          </div>
          <p className="text-sm text-gray-600 mt-2">{health.checks.database.message}</p>
          <p className="text-xs text-gray-400 mt-1">
            Response: {health.checks.database.responseTimeMs}ms
          </p>
        </div>

        {/* Cache Check */}
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex justify-between items-start">
            <h3 className="font-semibold">Cache</h3>
            <StatusBadge status={health.checks.cache.status} />
          </div>
          <p className="text-sm text-gray-600 mt-2">{health.checks.cache.message}</p>
          <p className="text-xs text-gray-400 mt-1">
            Driver: {health.checks.cache.driver}
          </p>
        </div>

        {/* Queue Check */}
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex justify-between items-start">
            <h3 className="font-semibold">Queue</h3>
            <StatusBadge status={health.checks.queue.status} />
          </div>
          <p className="text-sm text-gray-600 mt-2">
            Pending: {health.checks.queue.pendingJobs} | Failed: {health.checks.queue.failedJobs}
          </p>
          <p className="text-xs text-gray-400 mt-1">
            Driver: {health.checks.queue.driver}
          </p>
        </div>

        {/* Storage Check */}
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex justify-between items-start">
            <h3 className="font-semibold">Storage</h3>
            <StatusBadge status={health.checks.storage.status} />
          </div>
          <p className="text-sm text-gray-600 mt-2">{health.checks.storage.message}</p>
          <div className="mt-2 bg-gray-200 rounded-full h-2">
            <div
              className={`h-2 rounded-full ${
                health.checks.storage.usedPercent >= 95 ? 'bg-red-500' :
                health.checks.storage.usedPercent >= 85 ? 'bg-yellow-500' : 'bg-green-500'
              }`}
              style={{ width: `${health.checks.storage.usedPercent}%` }}
            />
          </div>
          <p className="text-xs text-gray-400 mt-1">
            {health.checks.storage.usedGb.toFixed(1)} GB / {health.checks.storage.totalGb.toFixed(1)} GB
          </p>
        </div>
      </div>

      {/* Database Metrics */}
      <div className="bg-white p-4 rounded-lg shadow">
        <h3 className="font-semibold mb-4">Database Tables (Top 15 by Size)</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b">
                <th className="text-left py-2">Table</th>
                <th className="text-right py-2">Rows</th>
                <th className="text-right py-2">Size (MB)</th>
              </tr>
            </thead>
            <tbody>
              {health.metrics.database.topTables.map((table) => (
                <tr key={table.name} className="border-b">
                  <td className="py-2 font-mono text-xs">{table.name}</td>
                  <td className="py-2 text-right">{table.rows.toLocaleString()}</td>
                  <td className="py-2 text-right">{table.sizeMb}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="text-sm text-gray-500 mt-2">
          Total Database Size: {health.metrics.database.totalSizeMb} MB
        </p>
      </div>

      {/* Application Metrics */}
      <div className="bg-white p-4 rounded-lg shadow">
        <h3 className="font-semibold mb-4">Application Metrics</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p className="text-2xl font-bold">{health.metrics.application.users}</p>
            <p className="text-sm text-gray-500">Users</p>
          </div>
          <div>
            <p className="text-2xl font-bold">{health.metrics.application.products}</p>
            <p className="text-sm text-gray-500">Products</p>
          </div>
          <div>
            <p className="text-2xl font-bold">{health.metrics.application.salesOrders}</p>
            <p className="text-sm text-gray-500">Sales Orders</p>
          </div>
          <div>
            <p className="text-2xl font-bold">{health.metrics.application.purchaseOrders}</p>
            <p className="text-sm text-gray-500">Purchase Orders</p>
          </div>
        </div>
      </div>

      {/* Recent Errors */}
      {health.metrics.errors.totalExceptionsLast24h > 0 && (
        <div className="bg-white p-4 rounded-lg shadow">
          <h3 className="font-semibold mb-4 text-red-600">
            Recent Errors ({health.metrics.errors.totalExceptionsLast24h} in last 24h)
          </h3>
          <div className="space-y-2">
            {health.metrics.errors.recentExceptions.slice(0, 5).map((error) => (
              <div key={error.id} className="border-l-4 border-red-500 pl-3 py-2">
                <p className="font-mono text-xs text-red-600">{error.class}</p>
                <p className="text-sm text-gray-600 truncate">{error.message}</p>
                <p className="text-xs text-gray-400">
                  {error.file}:{error.line} - {error.timestamp}
                </p>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `system-health.index` | View complete health status | god, admin, auditor |
| `system-health.database` | View database metrics | god, admin, auditor |
| `system-health.storage` | View storage metrics | god, admin, auditor |
| `system-health.queue` | View queue status | god, admin, auditor |
| `system-health.errors` | View error logs | god, admin, auditor |
| `system-health.metrics` | View application metrics | god, admin, auditor |

### Auditor Role

The `auditor` role has read-only access to all system health endpoints, making it suitable for:
- Compliance auditors who need to verify system operational status
- Security personnel monitoring system health and errors
- External consultants reviewing system performance

---

## Use Cases

### 1. Uptime Monitoring
Use the `/ping` endpoint with external monitoring services:
- UptimeRobot
- Pingdom
- Healthchecks.io
- AWS CloudWatch

### 2. Admin Dashboard
Display real-time system status on admin dashboard with auto-refresh.

### 3. Error Debugging
When users report issues, administrators can check:
- Recent exceptions and their stack traces
- Database response times
- Queue backlogs
- Storage availability

### 4. Capacity Planning
Monitor database growth and storage usage over time:
- Table size trends
- Record count growth
- Disk space consumption

---

## Best Practices

1. **Polling Frequency**: Use 30-60 second intervals for dashboard refresh to avoid overwhelming the API.

2. **Error Handling**: Always handle API errors gracefully in the frontend.

3. **Caching**: Consider caching health status for 10-15 seconds on high-traffic dashboards.

4. **Alerting**: Integrate with notification systems when status becomes `warning` or `critical`.

5. **Access Control**: Restrict system health endpoints to administrators only.

---

## Related Documentation

- [Audit Frontend Guide](AUDIT_FRONTEND_GUIDE.md) - For user activity tracking
- [Frontend Integration Guide](../FRONTEND_INTEGRATION_GUIDE.md) - General API usage
- [Authentication Guide](AUTH_FRONTEND_GUIDE.md) - Token management
