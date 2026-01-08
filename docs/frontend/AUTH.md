# Auth Module

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Logout (invalidate token) |
| POST | `/api/auth/register` | Register new user |
| GET | `/api/v1/users` | List users |
| GET | `/api/v1/users/{id}` | Get user |
| PATCH | `/api/v1/users/{id}` | Update user |

## Login

```typescript
interface LoginRequest {
  email: string;
  password: string;
}

interface LoginResponse {
  token: string;
  user: {
    id: number;
    name: string;
    email: string;
    roles: string[];
  };
}

// Example
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'secureadmin'
  })
});

const { token, user } = await response.json();
localStorage.setItem('token', token);
```

## Logout

```typescript
await fetch('/api/auth/logout', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` }
});
localStorage.removeItem('token');
```

## User Management

```typescript
interface User {
  id: string;
  name: string;
  email: string;
  createdAt: string;
  updatedAt: string;
}

// List users (admin only)
GET /api/v1/users

// Get current user
GET /api/v1/users/{id}

// Update user
PATCH /api/v1/users/{id}
{
  "data": {
    "type": "users",
    "id": "1",
    "attributes": {
      "name": "Updated Name"
    }
  }
}
```

## Roles & Permissions

| Role | Description | Access Level |
|------|-------------|--------------|
| god | Super admin | Full system access |
| admin | Administrator | Full module access |
| tech | Technical user | Read-only access |
| customer | Customer | Own resources only |

### Check Permissions
```typescript
// User object includes roles
const hasAdminAccess = user.roles.includes('admin') || user.roles.includes('god');
```

## Error Responses

```typescript
// Invalid credentials (401)
{
  "message": "Invalid credentials"
}

// Token expired (401)
{
  "message": "Unauthenticated"
}

// Forbidden (403)
{
  "message": "This action is unauthorized"
}
```
