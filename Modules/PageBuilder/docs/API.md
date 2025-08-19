# 📋 API Documentation - PageBuilder

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 Page

**Resource Type:** `pages`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pages` | List all Pages |
| POST | `/api/v1/pages` | Create new Page |
| GET | `/api/v1/pages/{id}` | Show specific Page |
| PATCH | `/api/v1/pages/{id}` | Update Page |
| DELETE | `/api/v1/pages/{id}` | Delete Page |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `title` | string | Auto-detected field |
| `slug` | string | Auto-detected field |
| `html` | string | Auto-detected field |
| `css` | string | Auto-detected field |
| `json` | object | Auto-detected field |
| `status` | string | Auto-detected field |
| `publishedAt` | datetime | Auto-detected field |
| `user` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/pages?filter[field]=value
```

#### Sorting
```
GET /api/v1/pages?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/pages?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

