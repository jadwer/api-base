# Auth Module - Frontend Integration Guide

**Module:** Auth
**Endpoints:** 4
**Base Path:** `/api/auth`

## Overview

The Auth module handles user authentication including login, logout, registration, and password management. Uses Laravel Sanctum for token-based authentication.

## Authentication Flow

### 1. Login

**Endpoint:** `POST /api/auth/login`
**Content-Type:** `application/json`

#### Request

```typescript
interface LoginRequest {
  email: string;
  password: string;
}
```

#### Response

```typescript
interface LoginResponse {
  access_token: string;
  token_type: 'Bearer';
  user: User;
}
```

#### Example Request

```bash
curl -X POST /api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "secureadmin"
  }'
```

#### Example Response

```json
{
  "access_token": "1|abc123...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "status": "active"
  }
}
```

#### Error Responses

| Status | Message |
|--------|---------|
| 401 | Credenciales inválidas. |
| 422 | Campos no permitidos: {fields} |
| 422 | Validation errors |

**Important:** The API returns 401 for:
- Invalid email
- Invalid password
- Deleted users (soft-deleted)
- Inactive users (status !== 'active')

---

### 2. Logout

**Endpoint:** `POST /api/auth/logout`
**Authentication:** Required

#### Request Headers

```
Authorization: Bearer {access_token}
```

#### Response

```json
{
  "message": "Sesión cerrada correctamente."
}
```

---

### 3. Register

**Endpoint:** `POST /api/auth/register`
**Content-Type:** `application/json`

#### Request

```typescript
interface RegisterRequest {
  name: string;      // max 255 chars
  email: string;     // unique
  password: string;  // min 8 chars
}
```

#### Response

```typescript
interface RegisterResponse {
  access_token: string;
  token_type: 'Bearer';
  user: User;
}
```

**Note:** Newly registered users are automatically assigned the `customer` role.

#### Validation Rules

| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| email | required, email, max:255, unique:users,email |
| password | required, string, min:8 |

---

### 4. Update Password

**Endpoint:** `PATCH /api/v1/profile/password`
**Authentication:** Required
**Content-Type:** `application/json`

Updates the password for the currently authenticated user.

---

## TypeScript Integration

### Types

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  status: 'active' | 'inactive' | 'suspended';
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
}
```

### Auth Service Example

```typescript
class AuthService {
  private baseUrl = '/api/auth';
  private token: string | null = null;

  async login(email: string, password: string): Promise<User> {
    const response = await fetch(`${this.baseUrl}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }

    const data = await response.json();
    this.token = data.access_token;
    localStorage.setItem('auth_token', data.access_token);
    return data.user;
  }

  async logout(): Promise<void> {
    await fetch(`${this.baseUrl}/logout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
      },
    });
    this.token = null;
    localStorage.removeItem('auth_token');
  }

  async register(name: string, email: string, password: string): Promise<User> {
    const response = await fetch(`${this.baseUrl}/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ name, email, password }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }

    const data = await response.json();
    this.token = data.access_token;
    localStorage.setItem('auth_token', data.access_token);
    return data.user;
  }

  getToken(): string | null {
    return this.token || localStorage.getItem('auth_token');
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }
}

export const authService = new AuthService();
```

### Axios Interceptor Example

```typescript
import axios from 'axios';
import { authService } from './auth.service';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = authService.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor for 401 handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      authService.logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## React Hook Example

```typescript
import { useState, useEffect, createContext, useContext } from 'react';
import { authService } from './auth.service';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check for existing token on mount
    const token = authService.getToken();
    if (token) {
      // Optionally validate token with API
      setIsLoading(false);
    } else {
      setIsLoading(false);
    }
  }, []);

  const login = async (email: string, password: string) => {
    const user = await authService.login(email, password);
    setUser(user);
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
  };

  const register = async (name: string, email: string, password: string) => {
    const user = await authService.register(name, email, password);
    setUser(user);
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout, register }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

---

## Security Considerations

1. **Token Storage:** Store tokens in `localStorage` or `sessionStorage`. For higher security, consider HTTP-only cookies.

2. **Token Expiration:** Sanctum tokens don't expire by default. Implement token refresh or session timeout on the frontend.

3. **HTTPS:** Always use HTTPS in production.

4. **CORS:** Configure allowed origins in `config/cors.php`.

5. **Rate Limiting:** The API may have rate limiting. Handle 429 responses gracefully.

---

## Error Handling

```typescript
interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

async function handleAuthError(response: Response): Promise<never> {
  const data = await response.json();

  if (response.status === 422 && data.errors) {
    // Validation errors
    const messages = Object.values(data.errors).flat().join(', ');
    throw new Error(messages);
  }

  throw new Error(data.message || 'Error de autenticación');
}
```
