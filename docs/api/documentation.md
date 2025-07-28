# API Documentation

**Generado:** 2025-07-28T10:28:53.413047Z

**Base URL:** `http://localhost/api/v1`

## 🔐 Autenticación

**Tipo:** Bearer Token

**Header:** `Authorization: Bearer {token}`

**Login:** `POST /api/auth/login`

## 📋 Endpoints

### 📦 Users

#### `GET` `api/v1/users`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `email` (string) 
- ✅ `status` (string) 
- ✅ `role` (string) 
- ✅ `roles` (relationship[]) 
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 
- ✅ `deleted_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/users",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/users`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `email` (string) 
- ✅ `status` (string) 
- ✅ `role` (string) 
- ✅ `roles` (relationship[]) 
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 
- ✅ `deleted_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/users",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "users",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/users/{user}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `email` (string) 
- ✅ `status` (string) 
- ✅ `role` (string) 
- ✅ `roles` (relationship[]) 
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 
- ✅ `deleted_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/users",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/users/{user}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `email` (string) 
- ✅ `status` (string) 
- ✅ `role` (string) 
- ✅ `roles` (relationship[]) 
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 
- ✅ `deleted_at` (datetime) 

---

#### `DELETE` `api/v1/users/{user}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `email` (string) 
- ✅ `status` (string) 
- ✅ `role` (string) 
- ✅ `roles` (relationship[]) 
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 
- ✅ `deleted_at` (datetime) 

---

### 📦 Audits

#### `GET` `api/v1/audits`

**Campos disponibles:**

- ✅ `event` (string) 
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/audits",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/audits`

**Campos disponibles:**

- ✅ `event` (string) 
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/audits",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "audits",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/audits/{audit}`

**Campos disponibles:**

- ✅ `event` (string) 
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/audits",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/audits/{audit}`

**Campos disponibles:**

- ✅ `event` (string) 
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

---

#### `DELETE` `api/v1/audits/{audit}`

**Campos disponibles:**

- ✅ `event` (string) 
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

---

### 📦 Pages

#### `GET` `api/v1/pages`

**Campos disponibles:**

- ✅ `title` (string) 
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `publishedAt` (datetime) 
- ✅ `user` (relationship) 
- ✅ `slug` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/pages",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/pages`

**Campos disponibles:**

- ✅ `title` (string) 
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `publishedAt` (datetime) 
- ✅ `user` (relationship) 
- ✅ `slug` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/pages",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "pages",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/pages/{page}`

**Campos disponibles:**

- ✅ `title` (string) 
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `publishedAt` (datetime) 
- ✅ `user` (relationship) 
- ✅ `slug` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/pages",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/pages/{page}`

**Campos disponibles:**

- ✅ `title` (string) 
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `publishedAt` (datetime) 
- ✅ `user` (relationship) 
- ✅ `slug` (mixed) 

---

#### `DELETE` `api/v1/pages/{page}`

**Campos disponibles:**

- ✅ `title` (string) 
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `publishedAt` (datetime) 
- ✅ `user` (relationship) 
- ✅ `slug` (mixed) 

---

### 📦 Roles

#### `GET` `api/v1/roles`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/roles",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/roles`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/roles",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "roles",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/roles/{role}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/roles",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/roles/{role}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

---

#### `DELETE` `api/v1/roles/{role}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

---

#### `GET` `api/v1/roles/{role}/permissions`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/roles",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `GET` `api/v1/roles/{role}/relationships/permissions`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/roles",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

### 📦 Permissions

#### `GET` `api/v1/permissions`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/permissions",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/permissions`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/permissions",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "permissions",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/permissions/{permission}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/permissions",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/permissions/{permission}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

---

#### `DELETE` `api/v1/permissions/{permission}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

---

### 📦 Products

#### `GET` `api/v1/products`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/products",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/products`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/products",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "products",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/products/{product}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/products",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/products/{product}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

#### `DELETE` `api/v1/products/{product}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

### 📦 Units

#### `GET` `api/v1/units`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/units",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/units`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/units",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "units",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/units/{unit}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/units",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/units/{unit}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

#### `DELETE` `api/v1/units/{unit}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

### 📦 Categories

#### `GET` `api/v1/categories`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/categories",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/categories`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/categories",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "categories",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/categories/{category}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/categories",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/categories/{category}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

#### `DELETE` `api/v1/categories/{category}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

### 📦 Brands

#### `GET` `api/v1/brands`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/brands",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/brands`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/brands",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "brands",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/brands/{brand}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/brands",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/brands/{brand}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

#### `DELETE` `api/v1/brands/{brand}`

**Campos disponibles:**

- ✅ `name` (string) 
- ✅ `description` (string) 
- ✅ `slug` (string) 
- ✅ `products` (relationship[]) 
- ✅ `created_at` (datetime) 
- ✅ `updated_at` (datetime) 

---

### 📦 Warehouses

#### `GET` `api/v1/warehouses`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/warehouses",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/warehouses`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/warehouses",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "warehouses",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/warehouses/{warehouse}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/warehouses",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/warehouses/{warehouse}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

#### `DELETE` `api/v1/warehouses/{warehouse}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

### 📦 Warehouse locations

#### `GET` `api/v1/warehouse-locations`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/warehouse_locations",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/warehouse-locations`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/warehouse_locations",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "warehouse_locations",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/warehouse-locations/{warehouse_location}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/warehouse_locations",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/warehouse-locations/{warehouse_location}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

#### `DELETE` `api/v1/warehouse-locations/{warehouse_location}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

### 📦 Product batches

#### `GET` `api/v1/product-batches`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/product_batches",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/product-batches`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/product_batches",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "product_batches",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/product-batches/{product_batch}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/product_batches",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/product-batches/{product_batch}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

#### `DELETE` `api/v1/product-batches/{product_batch}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

### 📦 Stocks

#### `GET` `api/v1/stocks`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/stocks",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/stocks`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/stocks",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "stocks",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/stocks/{stock}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/stocks",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/stocks/{stock}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

#### `DELETE` `api/v1/stocks/{stock}`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `product` (relationship) 
- ✅ `warehouse` (relationship) 
- ✅ `warehouseLocation` (relationship) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

---

### 📦 Profile

#### `PATCH` `api/v1/profile/password`

---

### 📦 Suppliers

#### `GET` `api/v1/suppliers`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/suppliers",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/suppliers`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/suppliers",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "suppliers",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/suppliers/{supplier}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/suppliers",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/suppliers/{supplier}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

#### `DELETE` `api/v1/suppliers/{supplier}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

### 📦 Purchase orders

#### `GET` `api/v1/purchase-orders`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/purchase_orders",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/purchase-orders`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/purchase_orders",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "purchase_orders",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/purchase-orders/{purchase_order}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/purchase_orders",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/purchase-orders/{purchase_order}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

#### `DELETE` `api/v1/purchase-orders/{purchase_order}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

### 📦 Purchase order items

#### `GET` `api/v1/purchase-order-items`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/purchase_order_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/purchase-order-items`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/purchase_order_items",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "purchase_order_items",
            "attributes": [
                "..."
            ]
        }
    }
}
```

---

#### `GET` `api/v1/purchase-order-items/{purchase_order_item}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/purchase_order_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/purchase-order-items/{purchase_order_item}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

#### `DELETE` `api/v1/purchase-order-items/{purchase_order_item}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `purchaseOrder` (relationship) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `quantity` (mixed) 

---

