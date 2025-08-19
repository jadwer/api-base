# 📋 API Documentation - User

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 User

**Resource Type:** `users`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List all Users |
| POST | `/api/v1/users` | Create new User |
| GET | `/api/v1/users/{id}` | Show specific User |
| PATCH | `/api/v1/users/{id}` | Update User |
| DELETE | `/api/v1/users/{id}` | Delete User |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `name` | string | Auto-detected field |
| `email` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `role` | string | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/users?filter[field]=value
```

#### Sorting
```
GET /api/v1/users?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/users?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

