# 📋 API Documentation - PermissionManager

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 Permission

**Resource Type:** `permissions`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/permissions` | List all Permissions |
| POST | `/api/v1/permissions` | Create new Permission |
| GET | `/api/v1/permissions/{id}` | Show specific Permission |
| PATCH | `/api/v1/permissions/{id}` | Update Permission |
| DELETE | `/api/v1/permissions/{id}` | Delete Permission |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `name` | string | Auto-detected field |
| `guard_name` | string | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/permissions?filter[field]=value
```

#### Sorting
```
GET /api/v1/permissions?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/permissions?page[number]=1&page[size]=20
```

## 📄 Role

**Resource Type:** `roles`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/roles` | List all Roles |
| POST | `/api/v1/roles` | Create new Role |
| GET | `/api/v1/roles/{id}` | Show specific Role |
| PATCH | `/api/v1/roles/{id}` | Update Role |
| DELETE | `/api/v1/roles/{id}` | Delete Role |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `name` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `guard_name` | string | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `permissions` | relationship[] | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/roles?filter[field]=value
```

#### Sorting
```
GET /api/v1/roles?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/roles?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

