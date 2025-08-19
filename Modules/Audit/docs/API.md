# 📋 API Documentation - Audit

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 Audit

**Resource Type:** `audits`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/audits` | List all Audits |
| POST | `/api/v1/audits` | Create new Audit |
| GET | `/api/v1/audits/{id}` | Show specific Audit |
| PATCH | `/api/v1/audits/{id}` | Update Audit |
| DELETE | `/api/v1/audits/{id}` | Delete Audit |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `event` | string | Auto-detected field |
| `userId` | number | Auto-detected field |
| `auditableType` | string | Auto-detected field |
| `auditableId` | number | Auto-detected field |
| `oldValues` | string | Auto-detected field |
| `newValues` | string | Auto-detected field |
| `ipAddress` | string | Auto-detected field |
| `userAgent` | string | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `causer` | unknown | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/audits?filter[field]=value
```

#### Sorting
```
GET /api/v1/audits?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/audits?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

