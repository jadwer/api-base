# SystemHealth Module

## Overview

System health monitoring and status checks. Used for dashboards and monitoring.

## Endpoint

```typescript
// No authentication required
GET /api/v1/system-health
```

## Response

```typescript
interface SystemHealthResponse {
  status: 'healthy' | 'degraded' | 'unhealthy';
  timestamp: string;
  version: string;
  services: {
    database: ServiceStatus;
    cache: ServiceStatus;
    queue: ServiceStatus;
    storage: ServiceStatus;
  };
  metrics?: {
    uptime: number;           // Seconds
    memory: MemoryInfo;
    requests: RequestMetrics;
  };
}

interface ServiceStatus {
  status: 'up' | 'down' | 'degraded';
  responseTime?: number;      // Milliseconds
  message?: string;
  lastChecked: string;
}

interface MemoryInfo {
  used: number;               // Bytes
  total: number;
  percentage: number;
}

interface RequestMetrics {
  total: number;
  lastHour: number;
  averageResponseTime: number;
}
```

## Example Response

```json
{
  "status": "healthy",
  "timestamp": "2026-01-08T10:30:00Z",
  "version": "1.0.0",
  "services": {
    "database": {
      "status": "up",
      "responseTime": 5,
      "lastChecked": "2026-01-08T10:30:00Z"
    },
    "cache": {
      "status": "up",
      "responseTime": 2,
      "lastChecked": "2026-01-08T10:30:00Z"
    },
    "queue": {
      "status": "up",
      "pending": 0,
      "lastChecked": "2026-01-08T10:30:00Z"
    },
    "storage": {
      "status": "up",
      "freeSpace": "50GB",
      "lastChecked": "2026-01-08T10:30:00Z"
    }
  },
  "metrics": {
    "uptime": 86400,
    "memory": {
      "used": 512000000,
      "total": 2048000000,
      "percentage": 25
    },
    "requests": {
      "total": 150000,
      "lastHour": 1500,
      "averageResponseTime": 120
    }
  }
}
```

## Status Codes

| HTTP Status | System Status | Meaning |
|-------------|---------------|---------|
| 200 | healthy | All services operational |
| 200 | degraded | Some services slow/degraded |
| 503 | unhealthy | Critical services down |

## Frontend Implementation

### Health Check Component

```typescript
interface HealthStatus {
  status: 'healthy' | 'degraded' | 'unhealthy';
  services: Record<string, { status: string; responseTime?: number }>;
  timestamp: string;
}

async function checkSystemHealth(): Promise<HealthStatus> {
  const response = await fetch('/api/v1/system-health');
  return response.json();
}

// React component
function SystemHealthIndicator() {
  const [health, setHealth] = useState<HealthStatus | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    const check = async () => {
      try {
        const data = await checkSystemHealth();
        setHealth(data);
        setError(false);
      } catch {
        setError(true);
      }
    };

    check();
    const interval = setInterval(check, 60000); // Check every minute
    return () => clearInterval(interval);
  }, []);

  if (error) {
    return <StatusBadge status="error" label="System Unavailable" />;
  }

  if (!health) {
    return <StatusBadge status="loading" label="Checking..." />;
  }

  return (
    <StatusBadge
      status={health.status}
      label={health.status === 'healthy' ? 'All Systems Operational' : 'Issues Detected'}
    />
  );
}
```

### Status Dashboard

```typescript
function SystemHealthDashboard() {
  const [health, setHealth] = useState(null);

  useEffect(() => {
    checkSystemHealth().then(setHealth);
  }, []);

  if (!health) return <Loading />;

  return (
    <div className="health-dashboard">
      <h2>System Status: {health.status}</h2>
      <p>Last checked: {health.timestamp}</p>

      <div className="services">
        {Object.entries(health.services).map(([name, service]) => (
          <div key={name} className={`service ${service.status}`}>
            <span className="name">{name}</span>
            <span className="status">{service.status}</span>
            {service.responseTime && (
              <span className="response-time">{service.responseTime}ms</span>
            )}
          </div>
        ))}
      </div>

      {health.metrics && (
        <div className="metrics">
          <div>Uptime: {formatUptime(health.metrics.uptime)}</div>
          <div>Memory: {health.metrics.memory.percentage}%</div>
          <div>Avg Response: {health.metrics.requests.averageResponseTime}ms</div>
        </div>
      )}
    </div>
  );
}
```

### Status Page Colors

| Status | Color | Icon |
|--------|-------|------|
| healthy / up | Green (#22c55e) | Check circle |
| degraded | Yellow (#eab308) | Warning triangle |
| unhealthy / down | Red (#ef4444) | X circle |

## Use Cases

1. **Login Page**: Show system status before login attempt
2. **Dashboard Header**: Small status indicator
3. **Admin Panel**: Full status dashboard
4. **Monitoring**: External monitoring integration
5. **Error Pages**: Check if system is down

## Polling Strategy

```typescript
// Recommended polling intervals
const INTERVALS = {
  statusIndicator: 60000,    // 1 minute (header badge)
  dashboard: 30000,          // 30 seconds (status page)
  critical: 10000,           // 10 seconds (during incidents)
};

// Stop polling when tab not visible
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stopPolling();
  } else {
    startPolling();
  }
});
```

## Error Handling

```typescript
// When health check fails, system might be completely down
async function safeHealthCheck() {
  try {
    const response = await fetch('/api/v1/system-health', {
      timeout: 5000  // 5 second timeout
    });

    if (!response.ok) {
      return { status: 'unhealthy', error: 'Service unavailable' };
    }

    return await response.json();
  } catch (error) {
    return {
      status: 'unhealthy',
      error: 'Cannot connect to server',
      offline: true
    };
  }
}
```
