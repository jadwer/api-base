# API Documentation

**Generado:** 2025-08-18T23:48:14.924879Z

**Base URL:** `http://localhost/api/v1`

## 🔐 Autenticación

**Tipo:** Bearer Token

**Header:** `Authorization: Bearer {token}`

**Login:** `POST /api/auth/login`

## 📋 Endpoints

### 📦 Users

#### `GET` `api/v1/users`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `status` (string) 
- ✅ `role` (string) 

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

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

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `status` (string) 
- ✅ `role` (string) 

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/users/{user}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `status` (string) 
- ✅ `role` (string) 

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

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

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `status` (string) 
- ✅ `role` (string) 

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/users\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "users",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/users/{user}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `status` (string) 
- ✅ `role` (string) 

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

---

### 📦 Audits

#### `GET` `api/v1/audits`

**Campos disponibles:**

- ✅ `event` (string) 🔄
- ✅ `userId` (number) 🔄
- ✅ `auditableType` (string) 🔄
- ✅ `auditableId` (number) 🔄
- ✅ `oldValues` (string) 
- ✅ `newValues` (string) 
- ✅ `ipAddress` (string) 
- ✅ `userAgent` (string) 
- ✅ `createdAt` (datetime) 🔄
- ✅ `updatedAt` (datetime) 🔄
- ✅ `causer` (mixed) 

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

- ✅ `event` (string) 🔄
- ✅ `userId` (number) 🔄
- ✅ `auditableType` (string) 🔄
- ✅ `auditableId` (number) 🔄
- ✅ `oldValues` (string) 
- ✅ `newValues` (string) 
- ✅ `ipAddress` (string) 
- ✅ `userAgent` (string) 
- ✅ `createdAt` (datetime) 🔄
- ✅ `updatedAt` (datetime) 🔄
- ✅ `causer` (mixed) 

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/audits/{audit}`

**Campos disponibles:**

- ✅ `event` (string) 🔄
- ✅ `userId` (number) 🔄
- ✅ `auditableType` (string) 🔄
- ✅ `auditableId` (number) 🔄
- ✅ `oldValues` (string) 
- ✅ `newValues` (string) 
- ✅ `ipAddress` (string) 
- ✅ `userAgent` (string) 
- ✅ `createdAt` (datetime) 🔄
- ✅ `updatedAt` (datetime) 🔄
- ✅ `causer` (mixed) 

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

- ✅ `event` (string) 🔄
- ✅ `userId` (number) 🔄
- ✅ `auditableType` (string) 🔄
- ✅ `auditableId` (number) 🔄
- ✅ `oldValues` (string) 
- ✅ `newValues` (string) 
- ✅ `ipAddress` (string) 
- ✅ `userAgent` (string) 
- ✅ `createdAt` (datetime) 🔄
- ✅ `updatedAt` (datetime) 🔄
- ✅ `causer` (mixed) 

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/audits\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "audits",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/audits/{audit}`

**Campos disponibles:**

- ✅ `event` (string) 🔄
- ✅ `userId` (number) 🔄
- ✅ `auditableType` (string) 🔄
- ✅ `auditableId` (number) 🔄
- ✅ `oldValues` (string) 
- ✅ `newValues` (string) 
- ✅ `ipAddress` (string) 
- ✅ `userAgent` (string) 
- ✅ `createdAt` (datetime) 🔄
- ✅ `updatedAt` (datetime) 🔄
- ✅ `causer` (mixed) 

---

### 📦 Pages

#### `GET` `api/v1/pages`

**Campos disponibles:**

- ✅ `title` (string) 🔄
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `status` (string) 🔄
- ✅ `publishedAt` (datetime) 🔄

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published,deleted,archived,active,inactive
- `publishedAt`: nullable, date

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

- ✅ `title` (string) 🔄
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `status` (string) 🔄
- ✅ `publishedAt` (datetime) 🔄

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published,deleted,archived,active,inactive
- `publishedAt`: nullable, date

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/pages/{page}`

**Campos disponibles:**

- ✅ `title` (string) 🔄
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `status` (string) 🔄
- ✅ `publishedAt` (datetime) 🔄

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published,deleted,archived,active,inactive
- `publishedAt`: nullable, date

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

- ✅ `title` (string) 🔄
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `status` (string) 🔄
- ✅ `publishedAt` (datetime) 🔄

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published,deleted,archived,active,inactive
- `publishedAt`: nullable, date

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/pages\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "pages",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/pages/{page}`

**Campos disponibles:**

- ✅ `title` (string) 🔄
- ✅ `slug` (string) 
- ✅ `html` (string) 
- ✅ `css` (string) 
- ✅ `json` (object) 
- ✅ `status` (string) 🔄
- ✅ `publishedAt` (datetime) 🔄

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published,deleted,archived,active,inactive
- `publishedAt`: nullable, date

---

### 📦 Roles

#### `GET` `api/v1/roles`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/roles/{role}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/roles\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "roles",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/roles/{role}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

---

#### `GET` `api/v1/roles/{role}/permissions`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `permissions` (relationship[])

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

- ✅ `name` (string) 🔄
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

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

- ✅ `name` (string) 🔄
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/permissions/{permission}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

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

- ✅ `name` (string) 🔄
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/permissions\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "permissions",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/permissions/{permission}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

---

### 📦 Products

#### `GET` `api/v1/products`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `sku` (string) 🔄
- ✅ `description` (string) 
- ✅ `fullDescription` (string) 
- ✅ `price` (number) 🔄
- ✅ `cost` (number) 🔄
- ✅ `iva` (boolean) 
- ✅ `imgPath` (string) 
- ✅ `datasheetPath` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `unit` (relationship)
- `category` (relationship)
- `brand` (relationship)

**Validaciones:**

- `name`: required, string, max:255
- `sku`: required, string, max:100, products, sku
- `description`: nullable, string, max:500
- `fullDescription`: nullable, string
- `price`: required, numeric, min:0
- `cost`: nullable, numeric, min:0
- `iva`: required, boolean
- `imgPath`: nullable, string
- `datasheetPath`: nullable, string

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

- ✅ `name` (string) 🔄
- ✅ `sku` (string) 🔄
- ✅ `description` (string) 
- ✅ `fullDescription` (string) 
- ✅ `price` (number) 🔄
- ✅ `cost` (number) 🔄
- ✅ `iva` (boolean) 
- ✅ `imgPath` (string) 
- ✅ `datasheetPath` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `unit` (relationship)
- `category` (relationship)
- `brand` (relationship)

**Validaciones:**

- `name`: required, string, max:255
- `sku`: required, string, max:100, products, sku
- `description`: nullable, string, max:500
- `fullDescription`: nullable, string
- `price`: required, numeric, min:0
- `cost`: nullable, numeric, min:0
- `iva`: required, boolean
- `imgPath`: nullable, string
- `datasheetPath`: nullable, string

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/products/{product}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `sku` (string) 🔄
- ✅ `description` (string) 
- ✅ `fullDescription` (string) 
- ✅ `price` (number) 🔄
- ✅ `cost` (number) 🔄
- ✅ `iva` (boolean) 
- ✅ `imgPath` (string) 
- ✅ `datasheetPath` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `unit` (relationship)
- `category` (relationship)
- `brand` (relationship)

**Validaciones:**

- `name`: required, string, max:255
- `sku`: required, string, max:100, products, sku
- `description`: nullable, string, max:500
- `fullDescription`: nullable, string
- `price`: required, numeric, min:0
- `cost`: nullable, numeric, min:0
- `iva`: required, boolean
- `imgPath`: nullable, string
- `datasheetPath`: nullable, string

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

- ✅ `name` (string) 🔄
- ✅ `sku` (string) 🔄
- ✅ `description` (string) 
- ✅ `fullDescription` (string) 
- ✅ `price` (number) 🔄
- ✅ `cost` (number) 🔄
- ✅ `iva` (boolean) 
- ✅ `imgPath` (string) 
- ✅ `datasheetPath` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `unit` (relationship)
- `category` (relationship)
- `brand` (relationship)

**Validaciones:**

- `name`: required, string, max:255
- `sku`: required, string, max:100, products, sku
- `description`: nullable, string, max:500
- `fullDescription`: nullable, string
- `price`: required, numeric, min:0
- `cost`: nullable, numeric, min:0
- `iva`: required, boolean
- `imgPath`: nullable, string
- `datasheetPath`: nullable, string

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/products\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "products",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/products/{product}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `sku` (string) 🔄
- ✅ `description` (string) 
- ✅ `fullDescription` (string) 
- ✅ `price` (number) 🔄
- ✅ `cost` (number) 🔄
- ✅ `iva` (boolean) 
- ✅ `imgPath` (string) 
- ✅ `datasheetPath` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `unit` (relationship)
- `category` (relationship)
- `brand` (relationship)

**Validaciones:**

- `name`: required, string, max:255
- `sku`: required, string, max:100, products, sku
- `description`: nullable, string, max:500
- `fullDescription`: nullable, string
- `price`: required, numeric, min:0
- `cost`: nullable, numeric, min:0
- `iva`: required, boolean
- `imgPath`: nullable, string
- `datasheetPath`: nullable, string

---

### 📦 Units

#### `GET` `api/v1/units`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `unitType` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, units, name
- `code`: required, string, max:10, units, code
- `unitType`: required, string

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

- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `unitType` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, units, name
- `code`: required, string, max:10, units, code
- `unitType`: required, string

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/units/{unit}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `unitType` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, units, name
- `code`: required, string, max:10, units, code
- `unitType`: required, string

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

- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `unitType` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, units, name
- `code`: required, string, max:10, units, code
- `unitType`: required, string

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/units\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "units",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/units/{unit}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `unitType` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, units, name
- `code`: required, string, max:10, units, code
- `unitType`: required, string

---

### 📦 Categories

#### `GET` `api/v1/categories`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, categories, name
- `description`: nullable, string, max:500

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, categories, name
- `description`: nullable, string, max:500

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/categories/{category}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, categories, name
- `description`: nullable, string, max:500

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, categories, name
- `description`: nullable, string, max:500

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/categories\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "categories",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/categories/{category}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, categories, name
- `description`: nullable, string, max:500

---

### 📦 Brands

#### `GET` `api/v1/brands`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/brands/{brand}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/brands\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "brands",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/brands/{brand}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `productsCount` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

---

### 📦 Warehouses

#### `GET` `api/v1/warehouses`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `slug` (string) 🔄
- ✅ `description` (string) 
- ✅ `code` (string) 🔄
- ✅ `warehouseType` (string) 🔄
- ✅ `address` (string) 
- ✅ `city` (string) 🔄
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `postalCode` (string) 
- ✅ `phone` (string) 
- ✅ `email` (string) 
- ✅ `managerName` (string) 
- ✅ `maxCapacity` (number) 
- ✅ `capacityUnit` (string) 
- ✅ `operatingHours` (string) 🔒
- ✅ `metadata` (string) 🔒
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `locations` (relationship[])
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `slug`: required, string, max:255, warehouses, slug
- `description`: nullable, string
- `code`: required, string, max:50, warehouses, code
- `warehouseType`: required, string, main, secondary, distribution, returns
- `address`: nullable, string
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:20
- `phone`: nullable, string, max:20
- `email`: nullable, email, max:255
- `managerName`: nullable, string, max:255
- `maxCapacity`: nullable, numeric, min:0
- `capacityUnit`: nullable, string, max:10
- `operatingHours`: nullable, array
- `metadata`: nullable, array
- `isActive`: sometimes, boolean

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

- ✅ `name` (string) 🔄
- ✅ `slug` (string) 🔄
- ✅ `description` (string) 
- ✅ `code` (string) 🔄
- ✅ `warehouseType` (string) 🔄
- ✅ `address` (string) 
- ✅ `city` (string) 🔄
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `postalCode` (string) 
- ✅ `phone` (string) 
- ✅ `email` (string) 
- ✅ `managerName` (string) 
- ✅ `maxCapacity` (number) 
- ✅ `capacityUnit` (string) 
- ✅ `operatingHours` (string) 🔒
- ✅ `metadata` (string) 🔒
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `locations` (relationship[])
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `slug`: required, string, max:255, warehouses, slug
- `description`: nullable, string
- `code`: required, string, max:50, warehouses, code
- `warehouseType`: required, string, main, secondary, distribution, returns
- `address`: nullable, string
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:20
- `phone`: nullable, string, max:20
- `email`: nullable, email, max:255
- `managerName`: nullable, string, max:255
- `maxCapacity`: nullable, numeric, min:0
- `capacityUnit`: nullable, string, max:10
- `operatingHours`: nullable, array
- `metadata`: nullable, array
- `isActive`: sometimes, boolean

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/warehouses/{warehouse}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `slug` (string) 🔄
- ✅ `description` (string) 
- ✅ `code` (string) 🔄
- ✅ `warehouseType` (string) 🔄
- ✅ `address` (string) 
- ✅ `city` (string) 🔄
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `postalCode` (string) 
- ✅ `phone` (string) 
- ✅ `email` (string) 
- ✅ `managerName` (string) 
- ✅ `maxCapacity` (number) 
- ✅ `capacityUnit` (string) 
- ✅ `operatingHours` (string) 🔒
- ✅ `metadata` (string) 🔒
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `locations` (relationship[])
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `slug`: required, string, max:255, warehouses, slug
- `description`: nullable, string
- `code`: required, string, max:50, warehouses, code
- `warehouseType`: required, string, main, secondary, distribution, returns
- `address`: nullable, string
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:20
- `phone`: nullable, string, max:20
- `email`: nullable, email, max:255
- `managerName`: nullable, string, max:255
- `maxCapacity`: nullable, numeric, min:0
- `capacityUnit`: nullable, string, max:10
- `operatingHours`: nullable, array
- `metadata`: nullable, array
- `isActive`: sometimes, boolean

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

- ✅ `name` (string) 🔄
- ✅ `slug` (string) 🔄
- ✅ `description` (string) 
- ✅ `code` (string) 🔄
- ✅ `warehouseType` (string) 🔄
- ✅ `address` (string) 
- ✅ `city` (string) 🔄
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `postalCode` (string) 
- ✅ `phone` (string) 
- ✅ `email` (string) 
- ✅ `managerName` (string) 
- ✅ `maxCapacity` (number) 
- ✅ `capacityUnit` (string) 
- ✅ `operatingHours` (string) 🔒
- ✅ `metadata` (string) 🔒
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `locations` (relationship[])
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `slug`: required, string, max:255, warehouses, slug
- `description`: nullable, string
- `code`: required, string, max:50, warehouses, code
- `warehouseType`: required, string, main, secondary, distribution, returns
- `address`: nullable, string
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:20
- `phone`: nullable, string, max:20
- `email`: nullable, email, max:255
- `managerName`: nullable, string, max:255
- `maxCapacity`: nullable, numeric, min:0
- `capacityUnit`: nullable, string, max:10
- `operatingHours`: nullable, array
- `metadata`: nullable, array
- `isActive`: sometimes, boolean

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/warehouses\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "warehouses",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/warehouses/{warehouse}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `slug` (string) 🔄
- ✅ `description` (string) 
- ✅ `code` (string) 🔄
- ✅ `warehouseType` (string) 🔄
- ✅ `address` (string) 
- ✅ `city` (string) 🔄
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `postalCode` (string) 
- ✅ `phone` (string) 
- ✅ `email` (string) 
- ✅ `managerName` (string) 
- ✅ `maxCapacity` (number) 
- ✅ `capacityUnit` (string) 
- ✅ `operatingHours` (string) 🔒
- ✅ `metadata` (string) 🔒
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `locations` (relationship[])
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `slug`: required, string, max:255, warehouses, slug
- `description`: nullable, string
- `code`: required, string, max:50, warehouses, code
- `warehouseType`: required, string, main, secondary, distribution, returns
- `address`: nullable, string
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:20
- `phone`: nullable, string, max:20
- `email`: nullable, email, max:255
- `managerName`: nullable, string, max:255
- `maxCapacity`: nullable, numeric, min:0
- `capacityUnit`: nullable, string, max:10
- `operatingHours`: nullable, array
- `metadata`: nullable, array
- `isActive`: sometimes, boolean

---

### 📦 Warehouse locations

#### `GET` `api/v1/warehouse-locations`

**Campos disponibles:**

- ✅ `warehouseId` (number) 
- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `description` (string) 
- ✅ `locationType` (string) 🔄
- ✅ `aisle` (string) 
- ✅ `rack` (string) 
- ✅ `shelf` (string) 
- ✅ `level` (string) 
- ✅ `position` (string) 
- ✅ `barcode` (string) 
- ✅ `maxWeight` (number) 
- ✅ `maxVolume` (number) 
- ✅ `dimensions` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `isPickable` (boolean) 
- ✅ `isReceivable` (boolean) 
- ✅ `priority` (number) 🔄
- ✅ `metadata` (array) 🔒
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `warehouse` (relationship)
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `code`: required, string, max:255, warehouse_locations, code
- `description`: nullable, string
- `locationType`: required, string, aisle, rack, shelf, bin, zone, bay
- `aisle`: nullable, string, max:255
- `rack`: nullable, string, max:255
- `shelf`: nullable, string, max:255
- `level`: nullable, string, max:255
- `position`: nullable, string, max:255
- `barcode`: nullable, string, max:255, warehouse_locations, barcode
- `maxWeight`: nullable, numeric, min:0
- `maxVolume`: nullable, numeric, min:0
- `dimensions`: nullable, string, max:255
- `isActive`: sometimes, boolean
- `isPickable`: sometimes, boolean
- `isReceivable`: sometimes, boolean
- `priority`: sometimes, integer, min:1, max:10
- `metadata`: nullable, array
- `warehouseId`: required, integer, exists:warehouses,id

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

- ✅ `warehouseId` (number) 
- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `description` (string) 
- ✅ `locationType` (string) 🔄
- ✅ `aisle` (string) 
- ✅ `rack` (string) 
- ✅ `shelf` (string) 
- ✅ `level` (string) 
- ✅ `position` (string) 
- ✅ `barcode` (string) 
- ✅ `maxWeight` (number) 
- ✅ `maxVolume` (number) 
- ✅ `dimensions` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `isPickable` (boolean) 
- ✅ `isReceivable` (boolean) 
- ✅ `priority` (number) 🔄
- ✅ `metadata` (array) 🔒
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `warehouse` (relationship)
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `code`: required, string, max:255, warehouse_locations, code
- `description`: nullable, string
- `locationType`: required, string, aisle, rack, shelf, bin, zone, bay
- `aisle`: nullable, string, max:255
- `rack`: nullable, string, max:255
- `shelf`: nullable, string, max:255
- `level`: nullable, string, max:255
- `position`: nullable, string, max:255
- `barcode`: nullable, string, max:255, warehouse_locations, barcode
- `maxWeight`: nullable, numeric, min:0
- `maxVolume`: nullable, numeric, min:0
- `dimensions`: nullable, string, max:255
- `isActive`: sometimes, boolean
- `isPickable`: sometimes, boolean
- `isReceivable`: sometimes, boolean
- `priority`: sometimes, integer, min:1, max:10
- `metadata`: nullable, array
- `warehouseId`: required, integer, exists:warehouses,id

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/warehouse-locations/{warehouse_location}`

**Campos disponibles:**

- ✅ `warehouseId` (number) 
- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `description` (string) 
- ✅ `locationType` (string) 🔄
- ✅ `aisle` (string) 
- ✅ `rack` (string) 
- ✅ `shelf` (string) 
- ✅ `level` (string) 
- ✅ `position` (string) 
- ✅ `barcode` (string) 
- ✅ `maxWeight` (number) 
- ✅ `maxVolume` (number) 
- ✅ `dimensions` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `isPickable` (boolean) 
- ✅ `isReceivable` (boolean) 
- ✅ `priority` (number) 🔄
- ✅ `metadata` (array) 🔒
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `warehouse` (relationship)
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `code`: required, string, max:255, warehouse_locations, code
- `description`: nullable, string
- `locationType`: required, string, aisle, rack, shelf, bin, zone, bay
- `aisle`: nullable, string, max:255
- `rack`: nullable, string, max:255
- `shelf`: nullable, string, max:255
- `level`: nullable, string, max:255
- `position`: nullable, string, max:255
- `barcode`: nullable, string, max:255, warehouse_locations, barcode
- `maxWeight`: nullable, numeric, min:0
- `maxVolume`: nullable, numeric, min:0
- `dimensions`: nullable, string, max:255
- `isActive`: sometimes, boolean
- `isPickable`: sometimes, boolean
- `isReceivable`: sometimes, boolean
- `priority`: sometimes, integer, min:1, max:10
- `metadata`: nullable, array
- `warehouseId`: required, integer, exists:warehouses,id

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

- ✅ `warehouseId` (number) 
- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `description` (string) 
- ✅ `locationType` (string) 🔄
- ✅ `aisle` (string) 
- ✅ `rack` (string) 
- ✅ `shelf` (string) 
- ✅ `level` (string) 
- ✅ `position` (string) 
- ✅ `barcode` (string) 
- ✅ `maxWeight` (number) 
- ✅ `maxVolume` (number) 
- ✅ `dimensions` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `isPickable` (boolean) 
- ✅ `isReceivable` (boolean) 
- ✅ `priority` (number) 🔄
- ✅ `metadata` (array) 🔒
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `warehouse` (relationship)
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `code`: required, string, max:255, warehouse_locations, code
- `description`: nullable, string
- `locationType`: required, string, aisle, rack, shelf, bin, zone, bay
- `aisle`: nullable, string, max:255
- `rack`: nullable, string, max:255
- `shelf`: nullable, string, max:255
- `level`: nullable, string, max:255
- `position`: nullable, string, max:255
- `barcode`: nullable, string, max:255, warehouse_locations, barcode
- `maxWeight`: nullable, numeric, min:0
- `maxVolume`: nullable, numeric, min:0
- `dimensions`: nullable, string, max:255
- `isActive`: sometimes, boolean
- `isPickable`: sometimes, boolean
- `isReceivable`: sometimes, boolean
- `priority`: sometimes, integer, min:1, max:10
- `metadata`: nullable, array
- `warehouseId`: required, integer, exists:warehouses,id

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/warehouse_locations\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "warehouse_locations",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/warehouse-locations/{warehouse_location}`

**Campos disponibles:**

- ✅ `warehouseId` (number) 
- ✅ `name` (string) 🔄
- ✅ `code` (string) 🔄
- ✅ `description` (string) 
- ✅ `locationType` (string) 🔄
- ✅ `aisle` (string) 
- ✅ `rack` (string) 
- ✅ `shelf` (string) 
- ✅ `level` (string) 
- ✅ `position` (string) 
- ✅ `barcode` (string) 
- ✅ `maxWeight` (number) 
- ✅ `maxVolume` (number) 
- ✅ `dimensions` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `isPickable` (boolean) 
- ✅ `isReceivable` (boolean) 
- ✅ `priority` (number) 🔄
- ✅ `metadata` (array) 🔒
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `warehouse` (relationship)
- `stock` (relationship[])
- `productBatches` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `code`: required, string, max:255, warehouse_locations, code
- `description`: nullable, string
- `locationType`: required, string, aisle, rack, shelf, bin, zone, bay
- `aisle`: nullable, string, max:255
- `rack`: nullable, string, max:255
- `shelf`: nullable, string, max:255
- `level`: nullable, string, max:255
- `position`: nullable, string, max:255
- `barcode`: nullable, string, max:255, warehouse_locations, barcode
- `maxWeight`: nullable, numeric, min:0
- `maxVolume`: nullable, numeric, min:0
- `dimensions`: nullable, string, max:255
- `isActive`: sometimes, boolean
- `isPickable`: sometimes, boolean
- `isReceivable`: sometimes, boolean
- `priority`: sometimes, integer, min:1, max:10
- `metadata`: nullable, array
- `warehouseId`: required, integer, exists:warehouses,id

---

### 📦 Product batches

#### `GET` `api/v1/product-batches`

**Campos disponibles:**

- ✅ `batchNumber` (string) 
- ✅ `lotNumber` (string) 
- ✅ `manufacturingDate` (datetime) 
- ✅ `expirationDate` (datetime) 
- ✅ `bestBeforeDate` (datetime) 
- ✅ `initialQuantity` (number) 
- ✅ `currentQuantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `supplierName` (string) 
- ✅ `supplierBatch` (string) 
- ✅ `qualityNotes` (string) 
- ✅ `testResults` (object) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `warehouseLocation` (relationship)

**Validaciones:**

- `batchNumber`: required, string, max:255, product_batches, batch_number
- `lotNumber`: sometimes, nullable, string, max:255
- `manufacturingDate`: sometimes, nullable, date
- `expirationDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `bestBeforeDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `initialQuantity`: required, numeric, min:0
- `currentQuantity`: required, numeric, min:0, lte:initialQuantity
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,expired,quarantine,recalled,consumed
- `supplierName`: sometimes, nullable, string, max:255
- `supplierBatch`: sometimes, nullable, string, max:255
- `qualityNotes`: sometimes, nullable, string
- `testResults`: sometimes, nullable, array
- `certifications`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `warehouseLocation`: sometimes

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

- ✅ `batchNumber` (string) 
- ✅ `lotNumber` (string) 
- ✅ `manufacturingDate` (datetime) 
- ✅ `expirationDate` (datetime) 
- ✅ `bestBeforeDate` (datetime) 
- ✅ `initialQuantity` (number) 
- ✅ `currentQuantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `supplierName` (string) 
- ✅ `supplierBatch` (string) 
- ✅ `qualityNotes` (string) 
- ✅ `testResults` (object) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `warehouseLocation` (relationship)

**Validaciones:**

- `batchNumber`: required, string, max:255, product_batches, batch_number
- `lotNumber`: sometimes, nullable, string, max:255
- `manufacturingDate`: sometimes, nullable, date
- `expirationDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `bestBeforeDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `initialQuantity`: required, numeric, min:0
- `currentQuantity`: required, numeric, min:0, lte:initialQuantity
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,expired,quarantine,recalled,consumed
- `supplierName`: sometimes, nullable, string, max:255
- `supplierBatch`: sometimes, nullable, string, max:255
- `qualityNotes`: sometimes, nullable, string
- `testResults`: sometimes, nullable, array
- `certifications`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `warehouseLocation`: sometimes

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/product-batches/{product_batch}`

**Campos disponibles:**

- ✅ `batchNumber` (string) 
- ✅ `lotNumber` (string) 
- ✅ `manufacturingDate` (datetime) 
- ✅ `expirationDate` (datetime) 
- ✅ `bestBeforeDate` (datetime) 
- ✅ `initialQuantity` (number) 
- ✅ `currentQuantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `supplierName` (string) 
- ✅ `supplierBatch` (string) 
- ✅ `qualityNotes` (string) 
- ✅ `testResults` (object) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `warehouseLocation` (relationship)

**Validaciones:**

- `batchNumber`: required, string, max:255, product_batches, batch_number
- `lotNumber`: sometimes, nullable, string, max:255
- `manufacturingDate`: sometimes, nullable, date
- `expirationDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `bestBeforeDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `initialQuantity`: required, numeric, min:0
- `currentQuantity`: required, numeric, min:0, lte:initialQuantity
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,expired,quarantine,recalled,consumed
- `supplierName`: sometimes, nullable, string, max:255
- `supplierBatch`: sometimes, nullable, string, max:255
- `qualityNotes`: sometimes, nullable, string
- `testResults`: sometimes, nullable, array
- `certifications`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `warehouseLocation`: sometimes

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

- ✅ `batchNumber` (string) 
- ✅ `lotNumber` (string) 
- ✅ `manufacturingDate` (datetime) 
- ✅ `expirationDate` (datetime) 
- ✅ `bestBeforeDate` (datetime) 
- ✅ `initialQuantity` (number) 
- ✅ `currentQuantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `supplierName` (string) 
- ✅ `supplierBatch` (string) 
- ✅ `qualityNotes` (string) 
- ✅ `testResults` (object) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `warehouseLocation` (relationship)

**Validaciones:**

- `batchNumber`: required, string, max:255, product_batches, batch_number
- `lotNumber`: sometimes, nullable, string, max:255
- `manufacturingDate`: sometimes, nullable, date
- `expirationDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `bestBeforeDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `initialQuantity`: required, numeric, min:0
- `currentQuantity`: required, numeric, min:0, lte:initialQuantity
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,expired,quarantine,recalled,consumed
- `supplierName`: sometimes, nullable, string, max:255
- `supplierBatch`: sometimes, nullable, string, max:255
- `qualityNotes`: sometimes, nullable, string
- `testResults`: sometimes, nullable, array
- `certifications`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `warehouseLocation`: sometimes

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/product_batches\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "product_batches",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/product-batches/{product_batch}`

**Campos disponibles:**

- ✅ `batchNumber` (string) 
- ✅ `lotNumber` (string) 
- ✅ `manufacturingDate` (datetime) 
- ✅ `expirationDate` (datetime) 
- ✅ `bestBeforeDate` (datetime) 
- ✅ `initialQuantity` (number) 
- ✅ `currentQuantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `supplierName` (string) 
- ✅ `supplierBatch` (string) 
- ✅ `qualityNotes` (string) 
- ✅ `testResults` (object) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `warehouseLocation` (relationship)

**Validaciones:**

- `batchNumber`: required, string, max:255, product_batches, batch_number
- `lotNumber`: sometimes, nullable, string, max:255
- `manufacturingDate`: sometimes, nullable, date
- `expirationDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `bestBeforeDate`: sometimes, nullable, date, after_or_equal:manufacturingDate
- `initialQuantity`: required, numeric, min:0
- `currentQuantity`: required, numeric, min:0, lte:initialQuantity
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,expired,quarantine,recalled,consumed
- `supplierName`: sometimes, nullable, string, max:255
- `supplierBatch`: sometimes, nullable, string, max:255
- `qualityNotes`: sometimes, nullable, string
- `testResults`: sometimes, nullable, array
- `certifications`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `warehouseLocation`: sometimes

---

### 📦 Stocks

#### `GET` `api/v1/stocks`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `quantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `minimumStock` (number) 
- ✅ `maximumStock` (number) 
- ✅ `reorderPoint` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `lastMovementDate` (datetime) 
- ✅ `lastMovementType` (string) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,inactive,quarantine,damaged
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `locationId`: sometimes, nullable, integer, exists:warehouse_locations,id

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

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `quantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `minimumStock` (number) 
- ✅ `maximumStock` (number) 
- ✅ `reorderPoint` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `lastMovementDate` (datetime) 
- ✅ `lastMovementType` (string) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,inactive,quarantine,damaged
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `locationId`: sometimes, nullable, integer, exists:warehouse_locations,id

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/stocks/{stock}`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `quantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `minimumStock` (number) 
- ✅ `maximumStock` (number) 
- ✅ `reorderPoint` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `lastMovementDate` (datetime) 
- ✅ `lastMovementType` (string) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,inactive,quarantine,damaged
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `locationId`: sometimes, nullable, integer, exists:warehouse_locations,id

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

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `quantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `minimumStock` (number) 
- ✅ `maximumStock` (number) 
- ✅ `reorderPoint` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `lastMovementDate` (datetime) 
- ✅ `lastMovementType` (string) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,inactive,quarantine,damaged
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `locationId`: sometimes, nullable, integer, exists:warehouse_locations,id

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/stocks\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "stocks",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/stocks/{stock}`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `quantity` (number) 
- ✅ `reservedQuantity` (number) 
- ✅ `availableQuantity` (number) 
- ✅ `minimumStock` (number) 
- ✅ `maximumStock` (number) 
- ✅ `reorderPoint` (number) 
- ✅ `unitCost` (number) 
- ✅ `totalValue` (number) 
- ✅ `status` (string) 
- ✅ `lastMovementDate` (datetime) 
- ✅ `lastMovementType` (string) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0
- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `unitCost`: required, numeric, min:0
- `status`: required, string, in:active,inactive,quarantine,damaged
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `locationId`: sometimes, nullable, integer, exists:warehouse_locations,id

---

### 📦 Inventory movements

#### `GET` `api/v1/inventory-movements`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `destinationWarehouseId` (number) 
- ✅ `destinationLocationId` (number) 
- ✅ `userId` (number) 
- ✅ `movementType` (string) 🔄
- ✅ `referenceType` (string) 🔄
- ✅ `referenceId` (number) 
- ✅ `movementDate` (datetime) 🔄
- ✅ `description` (string) 
- ✅ `quantity` (number) 🔄
- ✅ `unitCost` (number) 🔄
- ✅ `totalValue` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `previousStock` (number) 
- ✅ `newStock` (number) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)
- `destinationWarehouse` (relationship)
- `destinationLocation` (relationship)
- `user` (relationship)

**Validaciones:**

- `movementType`: required, string, entry, exit, transfer, adjustment
- `movementDate`: required, date
- `quantity`: required, numeric, not_in:0
- `unitCost`: required, numeric, min:0
- `referenceType`: nullable, string, purchase, sale, transfer, adjustment, manual
- `referenceId`: nullable, integer, min:1
- `description`: nullable, string, max:1000
- `status`: sometimes, string, pending, completed, cancelled
- `previousStock`: nullable, numeric
- `newStock`: nullable, numeric
- `batchInfo`: nullable, array
- `metadata`: nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `userId`: required, integer, exists:users,id
- `locationId`: nullable, integer, exists:warehouse_locations,id
- `destinationWarehouseId`: nullable, integer, exists:warehouses,id
- `destinationLocationId`: nullable, integer, exists:warehouse_locations,id

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/inventory_movements",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/inventory-movements`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `destinationWarehouseId` (number) 
- ✅ `destinationLocationId` (number) 
- ✅ `userId` (number) 
- ✅ `movementType` (string) 🔄
- ✅ `referenceType` (string) 🔄
- ✅ `referenceId` (number) 
- ✅ `movementDate` (datetime) 🔄
- ✅ `description` (string) 
- ✅ `quantity` (number) 🔄
- ✅ `unitCost` (number) 🔄
- ✅ `totalValue` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `previousStock` (number) 
- ✅ `newStock` (number) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)
- `destinationWarehouse` (relationship)
- `destinationLocation` (relationship)
- `user` (relationship)

**Validaciones:**

- `movementType`: required, string, entry, exit, transfer, adjustment
- `movementDate`: required, date
- `quantity`: required, numeric, not_in:0
- `unitCost`: required, numeric, min:0
- `referenceType`: nullable, string, purchase, sale, transfer, adjustment, manual
- `referenceId`: nullable, integer, min:1
- `description`: nullable, string, max:1000
- `status`: sometimes, string, pending, completed, cancelled
- `previousStock`: nullable, numeric
- `newStock`: nullable, numeric
- `batchInfo`: nullable, array
- `metadata`: nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `userId`: required, integer, exists:users,id
- `locationId`: nullable, integer, exists:warehouse_locations,id
- `destinationWarehouseId`: nullable, integer, exists:warehouses,id
- `destinationLocationId`: nullable, integer, exists:warehouse_locations,id

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/inventory_movements",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "inventory_movements",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/inventory-movements/{inventory_movement}`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `destinationWarehouseId` (number) 
- ✅ `destinationLocationId` (number) 
- ✅ `userId` (number) 
- ✅ `movementType` (string) 🔄
- ✅ `referenceType` (string) 🔄
- ✅ `referenceId` (number) 
- ✅ `movementDate` (datetime) 🔄
- ✅ `description` (string) 
- ✅ `quantity` (number) 🔄
- ✅ `unitCost` (number) 🔄
- ✅ `totalValue` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `previousStock` (number) 
- ✅ `newStock` (number) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)
- `destinationWarehouse` (relationship)
- `destinationLocation` (relationship)
- `user` (relationship)

**Validaciones:**

- `movementType`: required, string, entry, exit, transfer, adjustment
- `movementDate`: required, date
- `quantity`: required, numeric, not_in:0
- `unitCost`: required, numeric, min:0
- `referenceType`: nullable, string, purchase, sale, transfer, adjustment, manual
- `referenceId`: nullable, integer, min:1
- `description`: nullable, string, max:1000
- `status`: sometimes, string, pending, completed, cancelled
- `previousStock`: nullable, numeric
- `newStock`: nullable, numeric
- `batchInfo`: nullable, array
- `metadata`: nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `userId`: required, integer, exists:users,id
- `locationId`: nullable, integer, exists:warehouse_locations,id
- `destinationWarehouseId`: nullable, integer, exists:warehouses,id
- `destinationLocationId`: nullable, integer, exists:warehouse_locations,id

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/inventory_movements",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/inventory-movements/{inventory_movement}`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `destinationWarehouseId` (number) 
- ✅ `destinationLocationId` (number) 
- ✅ `userId` (number) 
- ✅ `movementType` (string) 🔄
- ✅ `referenceType` (string) 🔄
- ✅ `referenceId` (number) 
- ✅ `movementDate` (datetime) 🔄
- ✅ `description` (string) 
- ✅ `quantity` (number) 🔄
- ✅ `unitCost` (number) 🔄
- ✅ `totalValue` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `previousStock` (number) 
- ✅ `newStock` (number) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)
- `destinationWarehouse` (relationship)
- `destinationLocation` (relationship)
- `user` (relationship)

**Validaciones:**

- `movementType`: required, string, entry, exit, transfer, adjustment
- `movementDate`: required, date
- `quantity`: required, numeric, not_in:0
- `unitCost`: required, numeric, min:0
- `referenceType`: nullable, string, purchase, sale, transfer, adjustment, manual
- `referenceId`: nullable, integer, min:1
- `description`: nullable, string, max:1000
- `status`: sometimes, string, pending, completed, cancelled
- `previousStock`: nullable, numeric
- `newStock`: nullable, numeric
- `batchInfo`: nullable, array
- `metadata`: nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `userId`: required, integer, exists:users,id
- `locationId`: nullable, integer, exists:warehouse_locations,id
- `destinationWarehouseId`: nullable, integer, exists:warehouses,id
- `destinationLocationId`: nullable, integer, exists:warehouse_locations,id

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/inventory_movements\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "inventory_movements",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/inventory-movements/{inventory_movement}`

**Campos disponibles:**

- ✅ `productId` (number) 
- ✅ `warehouseId` (number) 
- ✅ `locationId` (number) 
- ✅ `destinationWarehouseId` (number) 
- ✅ `destinationLocationId` (number) 
- ✅ `userId` (number) 
- ✅ `movementType` (string) 🔄
- ✅ `referenceType` (string) 🔄
- ✅ `referenceId` (number) 
- ✅ `movementDate` (datetime) 🔄
- ✅ `description` (string) 
- ✅ `quantity` (number) 🔄
- ✅ `unitCost` (number) 🔄
- ✅ `totalValue` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `previousStock` (number) 
- ✅ `newStock` (number) 
- ✅ `batchInfo` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `product` (relationship)
- `warehouse` (relationship)
- `location` (relationship)
- `destinationWarehouse` (relationship)
- `destinationLocation` (relationship)
- `user` (relationship)

**Validaciones:**

- `movementType`: required, string, entry, exit, transfer, adjustment
- `movementDate`: required, date
- `quantity`: required, numeric, not_in:0
- `unitCost`: required, numeric, min:0
- `referenceType`: nullable, string, purchase, sale, transfer, adjustment, manual
- `referenceId`: nullable, integer, min:1
- `description`: nullable, string, max:1000
- `status`: sometimes, string, pending, completed, cancelled
- `previousStock`: nullable, numeric
- `newStock`: nullable, numeric
- `batchInfo`: nullable, array
- `metadata`: nullable, array
- `productId`: required, integer, exists:products,id
- `warehouseId`: required, integer, exists:warehouses,id
- `userId`: required, integer, exists:users,id
- `locationId`: nullable, integer, exists:warehouse_locations,id
- `destinationWarehouseId`: nullable, integer, exists:warehouses,id
- `destinationLocationId`: nullable, integer, exists:warehouse_locations,id

---

### 📦 Profile

#### `PATCH` `api/v1/profile/password`

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/profile\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "profile",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

### 📦 Contacts

#### `GET` `api/v1/contacts`

**Campos disponibles:**

- ✅ `contactType` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `legalName` (string) 🔄
- ✅ `taxId` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `website` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `isCustomer` (boolean) 🔄
- ✅ `isSupplier` (boolean) 🔄
- ✅ `creditLimit` (number) 🔄
- ✅ `currentCredit` (number) 🔄
- ✅ `classification` (string) 🔄
- ✅ `paymentTerms` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contactDocuments` (relationship[])
- `contactAddresses` (relationship[])
- `contactPeople` (relationship[])

**Validaciones:**

- `contactType`: required, person, company
- `name`: required, string, max:255
- `legalName`: nullable, string, max:255
- `taxId`: nullable, string, max:13

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contacts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/contacts`

**Campos disponibles:**

- ✅ `contactType` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `legalName` (string) 🔄
- ✅ `taxId` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `website` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `isCustomer` (boolean) 🔄
- ✅ `isSupplier` (boolean) 🔄
- ✅ `creditLimit` (number) 🔄
- ✅ `currentCredit` (number) 🔄
- ✅ `classification` (string) 🔄
- ✅ `paymentTerms` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contactDocuments` (relationship[])
- `contactAddresses` (relationship[])
- `contactPeople` (relationship[])

**Validaciones:**

- `contactType`: required, person, company
- `name`: required, string, max:255
- `legalName`: nullable, string, max:255
- `taxId`: nullable, string, max:13

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/contacts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contacts",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/contacts/{contact}`

**Campos disponibles:**

- ✅ `contactType` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `legalName` (string) 🔄
- ✅ `taxId` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `website` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `isCustomer` (boolean) 🔄
- ✅ `isSupplier` (boolean) 🔄
- ✅ `creditLimit` (number) 🔄
- ✅ `currentCredit` (number) 🔄
- ✅ `classification` (string) 🔄
- ✅ `paymentTerms` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contactDocuments` (relationship[])
- `contactAddresses` (relationship[])
- `contactPeople` (relationship[])

**Validaciones:**

- `contactType`: required, person, company
- `name`: required, string, max:255
- `legalName`: nullable, string, max:255
- `taxId`: nullable, string, max:13

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contacts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/contacts/{contact}`

**Campos disponibles:**

- ✅ `contactType` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `legalName` (string) 🔄
- ✅ `taxId` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `website` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `isCustomer` (boolean) 🔄
- ✅ `isSupplier` (boolean) 🔄
- ✅ `creditLimit` (number) 🔄
- ✅ `currentCredit` (number) 🔄
- ✅ `classification` (string) 🔄
- ✅ `paymentTerms` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contactDocuments` (relationship[])
- `contactAddresses` (relationship[])
- `contactPeople` (relationship[])

**Validaciones:**

- `contactType`: required, person, company
- `name`: required, string, max:255
- `legalName`: nullable, string, max:255
- `taxId`: nullable, string, max:13

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/contacts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contacts",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/contacts/{contact}`

**Campos disponibles:**

- ✅ `contactType` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `legalName` (string) 🔄
- ✅ `taxId` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `website` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `isCustomer` (boolean) 🔄
- ✅ `isSupplier` (boolean) 🔄
- ✅ `creditLimit` (number) 🔄
- ✅ `currentCredit` (number) 🔄
- ✅ `classification` (string) 🔄
- ✅ `paymentTerms` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contactDocuments` (relationship[])
- `contactAddresses` (relationship[])
- `contactPeople` (relationship[])

**Validaciones:**

- `contactType`: required, person, company
- `name`: required, string, max:255
- `legalName`: nullable, string, max:255
- `taxId`: nullable, string, max:13

---

### 📦 Contact documents

#### `GET` `api/v1/contact-documents`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `documentType` (string) 🔄
- ✅ `filePath` (string) 🔄
- ✅ `originalFilename` (string) 🔄
- ✅ `mimeType` (string) 🔄
- ✅ `fileSize` (number) 🔄
- ✅ `uploadedBy` (number) 🔄
- ✅ `verifiedAt` (datetime) 🔄
- ✅ `verifiedBy` (number) 🔄
- ✅ `expiresAt` (datetime) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255
- `file_path`: nullable, string, max:255
- `original_filename`: nullable, string, max:255
- `mime_type`: nullable, string, max:255
- `file_size`: nullable, integer
- `uploaded_by`: nullable, integer
- `verified_at`: nullable, date
- `verified_by`: nullable, integer
- `expires_at`: nullable, date
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_documents",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/contact-documents`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `documentType` (string) 🔄
- ✅ `filePath` (string) 🔄
- ✅ `originalFilename` (string) 🔄
- ✅ `mimeType` (string) 🔄
- ✅ `fileSize` (number) 🔄
- ✅ `uploadedBy` (number) 🔄
- ✅ `verifiedAt` (datetime) 🔄
- ✅ `verifiedBy` (number) 🔄
- ✅ `expiresAt` (datetime) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255
- `file_path`: nullable, string, max:255
- `original_filename`: nullable, string, max:255
- `mime_type`: nullable, string, max:255
- `file_size`: nullable, integer
- `uploaded_by`: nullable, integer
- `verified_at`: nullable, date
- `verified_by`: nullable, integer
- `expires_at`: nullable, date
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/contact_documents",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_documents",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/contact-documents/{contact_document}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `documentType` (string) 🔄
- ✅ `filePath` (string) 🔄
- ✅ `originalFilename` (string) 🔄
- ✅ `mimeType` (string) 🔄
- ✅ `fileSize` (number) 🔄
- ✅ `uploadedBy` (number) 🔄
- ✅ `verifiedAt` (datetime) 🔄
- ✅ `verifiedBy` (number) 🔄
- ✅ `expiresAt` (datetime) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255
- `file_path`: nullable, string, max:255
- `original_filename`: nullable, string, max:255
- `mime_type`: nullable, string, max:255
- `file_size`: nullable, integer
- `uploaded_by`: nullable, integer
- `verified_at`: nullable, date
- `verified_by`: nullable, integer
- `expires_at`: nullable, date
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_documents",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/contact-documents/{contact_document}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `documentType` (string) 🔄
- ✅ `filePath` (string) 🔄
- ✅ `originalFilename` (string) 🔄
- ✅ `mimeType` (string) 🔄
- ✅ `fileSize` (number) 🔄
- ✅ `uploadedBy` (number) 🔄
- ✅ `verifiedAt` (datetime) 🔄
- ✅ `verifiedBy` (number) 🔄
- ✅ `expiresAt` (datetime) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255
- `file_path`: nullable, string, max:255
- `original_filename`: nullable, string, max:255
- `mime_type`: nullable, string, max:255
- `file_size`: nullable, integer
- `uploaded_by`: nullable, integer
- `verified_at`: nullable, date
- `verified_by`: nullable, integer
- `expires_at`: nullable, date
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/contact_documents\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_documents",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/contact-documents/{contact_document}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `documentType` (string) 🔄
- ✅ `filePath` (string) 🔄
- ✅ `originalFilename` (string) 🔄
- ✅ `mimeType` (string) 🔄
- ✅ `fileSize` (number) 🔄
- ✅ `uploadedBy` (number) 🔄
- ✅ `verifiedAt` (datetime) 🔄
- ✅ `verifiedBy` (number) 🔄
- ✅ `expiresAt` (datetime) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255
- `file_path`: nullable, string, max:255
- `original_filename`: nullable, string, max:255
- `mime_type`: nullable, string, max:255
- `file_size`: nullable, integer
- `uploaded_by`: nullable, integer
- `verified_at`: nullable, date
- `verified_by`: nullable, integer
- `expires_at`: nullable, date
- `notes`: nullable, string
- `metadata`: nullable, array

---

### 📦 Contact addresses

#### `GET` `api/v1/contact-addresses`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `addressType` (string) 🔄
- ✅ `addressLine1` (string) 🔄
- ✅ `addressLine2` (string) 🔄
- ✅ `city` (string) 🔄
- ✅ `state` (string) 🔄
- ✅ `country` (string) 🔄
- ✅ `postalCode` (string) 🔄
- ✅ `isDefault` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contactId`: required, integer
- `addressType`: nullable, string, max:255
- `addressLine1`: nullable, string, max:255
- `addressLine2`: nullable, string, max:255
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:255
- `isDefault`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_addresses",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/contact-addresses`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `addressType` (string) 🔄
- ✅ `addressLine1` (string) 🔄
- ✅ `addressLine2` (string) 🔄
- ✅ `city` (string) 🔄
- ✅ `state` (string) 🔄
- ✅ `country` (string) 🔄
- ✅ `postalCode` (string) 🔄
- ✅ `isDefault` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contactId`: required, integer
- `addressType`: nullable, string, max:255
- `addressLine1`: nullable, string, max:255
- `addressLine2`: nullable, string, max:255
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:255
- `isDefault`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/contact_addresses",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_addresses",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/contact-addresses/{contact_address}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `addressType` (string) 🔄
- ✅ `addressLine1` (string) 🔄
- ✅ `addressLine2` (string) 🔄
- ✅ `city` (string) 🔄
- ✅ `state` (string) 🔄
- ✅ `country` (string) 🔄
- ✅ `postalCode` (string) 🔄
- ✅ `isDefault` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contactId`: required, integer
- `addressType`: nullable, string, max:255
- `addressLine1`: nullable, string, max:255
- `addressLine2`: nullable, string, max:255
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:255
- `isDefault`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_addresses",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/contact-addresses/{contact_address}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `addressType` (string) 🔄
- ✅ `addressLine1` (string) 🔄
- ✅ `addressLine2` (string) 🔄
- ✅ `city` (string) 🔄
- ✅ `state` (string) 🔄
- ✅ `country` (string) 🔄
- ✅ `postalCode` (string) 🔄
- ✅ `isDefault` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contactId`: required, integer
- `addressType`: nullable, string, max:255
- `addressLine1`: nullable, string, max:255
- `addressLine2`: nullable, string, max:255
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:255
- `isDefault`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/contact_addresses\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_addresses",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/contact-addresses/{contact_address}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `addressType` (string) 🔄
- ✅ `addressLine1` (string) 🔄
- ✅ `addressLine2` (string) 🔄
- ✅ `city` (string) 🔄
- ✅ `state` (string) 🔄
- ✅ `country` (string) 🔄
- ✅ `postalCode` (string) 🔄
- ✅ `isDefault` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contactId`: required, integer
- `addressType`: nullable, string, max:255
- `addressLine1`: nullable, string, max:255
- `addressLine2`: nullable, string, max:255
- `city`: nullable, string, max:255
- `state`: nullable, string, max:255
- `country`: nullable, string, max:255
- `postalCode`: nullable, string, max:255
- `isDefault`: nullable, boolean
- `metadata`: nullable, array

---

### 📦 Contact people

#### `GET` `api/v1/contact-people`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `name` (string) 🔄
- ✅ `position` (string) 🔄
- ✅ `department` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `mobile` (string) 🔄
- ✅ `isPrimary` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `name`: nullable, string, max:255
- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `is_primary`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_people",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/contact-people`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `name` (string) 🔄
- ✅ `position` (string) 🔄
- ✅ `department` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `mobile` (string) 🔄
- ✅ `isPrimary` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `name`: nullable, string, max:255
- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `is_primary`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/contact_people",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_people",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/contact-people/{contact_person}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `name` (string) 🔄
- ✅ `position` (string) 🔄
- ✅ `department` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `mobile` (string) 🔄
- ✅ `isPrimary` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `name`: nullable, string, max:255
- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `is_primary`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/contact_people",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/contact-people/{contact_person}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `name` (string) 🔄
- ✅ `position` (string) 🔄
- ✅ `department` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `mobile` (string) 🔄
- ✅ `isPrimary` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `name`: nullable, string, max:255
- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `is_primary`: nullable, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/contact_people\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "contact_people",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/contact-people/{contact_person}`

**Campos disponibles:**

- ✅ `contactId` (number) 🔄
- ✅ `name` (string) 🔄
- ✅ `position` (string) 🔄
- ✅ `department` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 🔄
- ✅ `mobile` (string) 🔄
- ✅ `isPrimary` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `name`: nullable, string, max:255
- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `is_primary`: nullable, boolean
- `metadata`: nullable, array

---

### 📦 Shopping carts

#### `GET` `api/v1/shopping-carts`

**Campos disponibles:**

- ✅ `sessionId` (string) 
- ✅ `userId` (string) 
- ✅ `status` (string) 🔄
- ✅ `expiresAt` (datetime) 
- ✅ `totalAmount` (number) 
- ✅ `currency` (string) 
- ✅ `couponCode` (string) 
- ✅ `discountAmount` (number) 
- ✅ `taxAmount` (number) 
- ✅ `shippingAmount` (number) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `cartItems` (relationship[])
- `user` (relationship)

**Validaciones:**

- `sessionId`: nullable, string, max:255
- `user`: nullable
- `status`: required, string, in:active,inactive,expired
- `expiresAt`: nullable, date
- `totalAmount`: required, numeric, min:0
- `currency`: required, string, max:3
- `couponCode`: nullable, string, max:255
- `discountAmount`: nullable, numeric, min:0
- `taxAmount`: nullable, numeric, min:0
- `shippingAmount`: nullable, numeric, min:0
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/shopping_carts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/shopping-carts`

**Campos disponibles:**

- ✅ `sessionId` (string) 
- ✅ `userId` (string) 
- ✅ `status` (string) 🔄
- ✅ `expiresAt` (datetime) 
- ✅ `totalAmount` (number) 
- ✅ `currency` (string) 
- ✅ `couponCode` (string) 
- ✅ `discountAmount` (number) 
- ✅ `taxAmount` (number) 
- ✅ `shippingAmount` (number) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `cartItems` (relationship[])
- `user` (relationship)

**Validaciones:**

- `sessionId`: nullable, string, max:255
- `user`: nullable
- `status`: required, string, in:active,inactive,expired
- `expiresAt`: nullable, date
- `totalAmount`: required, numeric, min:0
- `currency`: required, string, max:3
- `couponCode`: nullable, string, max:255
- `discountAmount`: nullable, numeric, min:0
- `taxAmount`: nullable, numeric, min:0
- `shippingAmount`: nullable, numeric, min:0
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/shopping_carts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "shopping_carts",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/shopping-carts/{shopping_cart}`

**Campos disponibles:**

- ✅ `sessionId` (string) 
- ✅ `userId` (string) 
- ✅ `status` (string) 🔄
- ✅ `expiresAt` (datetime) 
- ✅ `totalAmount` (number) 
- ✅ `currency` (string) 
- ✅ `couponCode` (string) 
- ✅ `discountAmount` (number) 
- ✅ `taxAmount` (number) 
- ✅ `shippingAmount` (number) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `cartItems` (relationship[])
- `user` (relationship)

**Validaciones:**

- `sessionId`: nullable, string, max:255
- `user`: nullable
- `status`: required, string, in:active,inactive,expired
- `expiresAt`: nullable, date
- `totalAmount`: required, numeric, min:0
- `currency`: required, string, max:3
- `couponCode`: nullable, string, max:255
- `discountAmount`: nullable, numeric, min:0
- `taxAmount`: nullable, numeric, min:0
- `shippingAmount`: nullable, numeric, min:0
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/shopping_carts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/shopping-carts/{shopping_cart}`

**Campos disponibles:**

- ✅ `sessionId` (string) 
- ✅ `userId` (string) 
- ✅ `status` (string) 🔄
- ✅ `expiresAt` (datetime) 
- ✅ `totalAmount` (number) 
- ✅ `currency` (string) 
- ✅ `couponCode` (string) 
- ✅ `discountAmount` (number) 
- ✅ `taxAmount` (number) 
- ✅ `shippingAmount` (number) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `cartItems` (relationship[])
- `user` (relationship)

**Validaciones:**

- `sessionId`: nullable, string, max:255
- `user`: nullable
- `status`: required, string, in:active,inactive,expired
- `expiresAt`: nullable, date
- `totalAmount`: required, numeric, min:0
- `currency`: required, string, max:3
- `couponCode`: nullable, string, max:255
- `discountAmount`: nullable, numeric, min:0
- `taxAmount`: nullable, numeric, min:0
- `shippingAmount`: nullable, numeric, min:0
- `notes`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/shopping_carts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "shopping_carts",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/shopping-carts/{shopping_cart}`

**Campos disponibles:**

- ✅ `sessionId` (string) 
- ✅ `userId` (string) 
- ✅ `status` (string) 🔄
- ✅ `expiresAt` (datetime) 
- ✅ `totalAmount` (number) 
- ✅ `currency` (string) 
- ✅ `couponCode` (string) 
- ✅ `discountAmount` (number) 
- ✅ `taxAmount` (number) 
- ✅ `shippingAmount` (number) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `cartItems` (relationship[])
- `user` (relationship)

**Validaciones:**

- `sessionId`: nullable, string, max:255
- `user`: nullable
- `status`: required, string, in:active,inactive,expired
- `expiresAt`: nullable, date
- `totalAmount`: required, numeric, min:0
- `currency`: required, string, max:3
- `couponCode`: nullable, string, max:255
- `discountAmount`: nullable, numeric, min:0
- `taxAmount`: nullable, numeric, min:0
- `shippingAmount`: nullable, numeric, min:0
- `notes`: nullable, string
- `metadata`: nullable, array

---

### 📦 Cart items

#### `GET` `api/v1/cart-items`

**Campos disponibles:**

- ✅ `shoppingCartId` (string) 
- ✅ `productId` (string) 
- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `originalPrice` (number) 
- ✅ `discountPercent` (number) 
- ✅ `discountAmount` (number) 
- ✅ `subtotal` (number) 
- ✅ `taxRate` (number) 
- ✅ `taxAmount` (number) 
- ✅ `total` (number) 
- ✅ `metadata` (object) 
- ✅ `status` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `shoppingCart` (relationship)
- `product` (relationship)

**Validaciones:**

- `shoppingCart`: required
- `product`: required
- `quantity`: required, numeric, min:0
- `unitPrice`: required, numeric, min:0
- `originalPrice`: required, numeric, min:0
- `discountPercent`: required, numeric, min:0, max:100
- `discountAmount`: required, numeric, min:0
- `subtotal`: required, numeric, min:0
- `taxRate`: required, numeric, min:0, max:100
- `taxAmount`: required, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array
- `status`: nullable, string, in:active,inactive

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/cart_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/cart-items`

**Campos disponibles:**

- ✅ `shoppingCartId` (string) 
- ✅ `productId` (string) 
- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `originalPrice` (number) 
- ✅ `discountPercent` (number) 
- ✅ `discountAmount` (number) 
- ✅ `subtotal` (number) 
- ✅ `taxRate` (number) 
- ✅ `taxAmount` (number) 
- ✅ `total` (number) 
- ✅ `metadata` (object) 
- ✅ `status` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `shoppingCart` (relationship)
- `product` (relationship)

**Validaciones:**

- `shoppingCart`: required
- `product`: required
- `quantity`: required, numeric, min:0
- `unitPrice`: required, numeric, min:0
- `originalPrice`: required, numeric, min:0
- `discountPercent`: required, numeric, min:0, max:100
- `discountAmount`: required, numeric, min:0
- `subtotal`: required, numeric, min:0
- `taxRate`: required, numeric, min:0, max:100
- `taxAmount`: required, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array
- `status`: nullable, string, in:active,inactive

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/cart_items",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "cart_items",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/cart-items/{cart_item}`

**Campos disponibles:**

- ✅ `shoppingCartId` (string) 
- ✅ `productId` (string) 
- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `originalPrice` (number) 
- ✅ `discountPercent` (number) 
- ✅ `discountAmount` (number) 
- ✅ `subtotal` (number) 
- ✅ `taxRate` (number) 
- ✅ `taxAmount` (number) 
- ✅ `total` (number) 
- ✅ `metadata` (object) 
- ✅ `status` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `shoppingCart` (relationship)
- `product` (relationship)

**Validaciones:**

- `shoppingCart`: required
- `product`: required
- `quantity`: required, numeric, min:0
- `unitPrice`: required, numeric, min:0
- `originalPrice`: required, numeric, min:0
- `discountPercent`: required, numeric, min:0, max:100
- `discountAmount`: required, numeric, min:0
- `subtotal`: required, numeric, min:0
- `taxRate`: required, numeric, min:0, max:100
- `taxAmount`: required, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array
- `status`: nullable, string, in:active,inactive

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/cart_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/cart-items/{cart_item}`

**Campos disponibles:**

- ✅ `shoppingCartId` (string) 
- ✅ `productId` (string) 
- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `originalPrice` (number) 
- ✅ `discountPercent` (number) 
- ✅ `discountAmount` (number) 
- ✅ `subtotal` (number) 
- ✅ `taxRate` (number) 
- ✅ `taxAmount` (number) 
- ✅ `total` (number) 
- ✅ `metadata` (object) 
- ✅ `status` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `shoppingCart` (relationship)
- `product` (relationship)

**Validaciones:**

- `shoppingCart`: required
- `product`: required
- `quantity`: required, numeric, min:0
- `unitPrice`: required, numeric, min:0
- `originalPrice`: required, numeric, min:0
- `discountPercent`: required, numeric, min:0, max:100
- `discountAmount`: required, numeric, min:0
- `subtotal`: required, numeric, min:0
- `taxRate`: required, numeric, min:0, max:100
- `taxAmount`: required, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array
- `status`: nullable, string, in:active,inactive

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/cart_items\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "cart_items",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/cart-items/{cart_item}`

**Campos disponibles:**

- ✅ `shoppingCartId` (string) 
- ✅ `productId` (string) 
- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `originalPrice` (number) 
- ✅ `discountPercent` (number) 
- ✅ `discountAmount` (number) 
- ✅ `subtotal` (number) 
- ✅ `taxRate` (number) 
- ✅ `taxAmount` (number) 
- ✅ `total` (number) 
- ✅ `metadata` (object) 
- ✅ `status` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `shoppingCart` (relationship)
- `product` (relationship)

**Validaciones:**

- `shoppingCart`: required
- `product`: required
- `quantity`: required, numeric, min:0
- `unitPrice`: required, numeric, min:0
- `originalPrice`: required, numeric, min:0
- `discountPercent`: required, numeric, min:0, max:100
- `discountAmount`: required, numeric, min:0
- `subtotal`: required, numeric, min:0
- `taxRate`: required, numeric, min:0, max:100
- `taxAmount`: required, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array
- `status`: nullable, string, in:active,inactive

---

### 📦 Coupons

#### `GET` `api/v1/coupons`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `couponType` (string) 🔄
- ✅ `value` (number) 
- ✅ `minAmount` (number) 
- ✅ `maxAmount` (number) 
- ✅ `maxUses` (number) 
- ✅ `usedCount` (number) 
- ✅ `startsAt` (datetime) 
- ✅ `expiresAt` (datetime) 
- ✅ `isActive` (boolean) 🔄
- ✅ `customerIds` (array) 
- ✅ `productIds` (array) 
- ✅ `categoryIds` (array) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: sometimes, required, string, max:255, coupons
- `name`: sometimes, required, string, max:255
- `description`: nullable, string
- `couponType`: sometimes, required, string, max:255
- `value`: sometimes, required, numeric, min:0
- `minAmount`: nullable, numeric, min:0
- `maxAmount`: nullable, numeric, min:0
- `maxUses`: nullable, integer, min:1
- `usedCount`: nullable, integer, min:0
- `startsAt`: nullable, date
- `expiresAt`: nullable, date, after:startsAt
- `isActive`: sometimes, required, boolean
- `customerIds`: nullable, array
- `productIds`: nullable, array
- `categoryIds`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/coupons",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/coupons`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `couponType` (string) 🔄
- ✅ `value` (number) 
- ✅ `minAmount` (number) 
- ✅ `maxAmount` (number) 
- ✅ `maxUses` (number) 
- ✅ `usedCount` (number) 
- ✅ `startsAt` (datetime) 
- ✅ `expiresAt` (datetime) 
- ✅ `isActive` (boolean) 🔄
- ✅ `customerIds` (array) 
- ✅ `productIds` (array) 
- ✅ `categoryIds` (array) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: sometimes, required, string, max:255, coupons
- `name`: sometimes, required, string, max:255
- `description`: nullable, string
- `couponType`: sometimes, required, string, max:255
- `value`: sometimes, required, numeric, min:0
- `minAmount`: nullable, numeric, min:0
- `maxAmount`: nullable, numeric, min:0
- `maxUses`: nullable, integer, min:1
- `usedCount`: nullable, integer, min:0
- `startsAt`: nullable, date
- `expiresAt`: nullable, date, after:startsAt
- `isActive`: sometimes, required, boolean
- `customerIds`: nullable, array
- `productIds`: nullable, array
- `categoryIds`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/coupons",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "coupons",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/coupons/{coupon}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `couponType` (string) 🔄
- ✅ `value` (number) 
- ✅ `minAmount` (number) 
- ✅ `maxAmount` (number) 
- ✅ `maxUses` (number) 
- ✅ `usedCount` (number) 
- ✅ `startsAt` (datetime) 
- ✅ `expiresAt` (datetime) 
- ✅ `isActive` (boolean) 🔄
- ✅ `customerIds` (array) 
- ✅ `productIds` (array) 
- ✅ `categoryIds` (array) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: sometimes, required, string, max:255, coupons
- `name`: sometimes, required, string, max:255
- `description`: nullable, string
- `couponType`: sometimes, required, string, max:255
- `value`: sometimes, required, numeric, min:0
- `minAmount`: nullable, numeric, min:0
- `maxAmount`: nullable, numeric, min:0
- `maxUses`: nullable, integer, min:1
- `usedCount`: nullable, integer, min:0
- `startsAt`: nullable, date
- `expiresAt`: nullable, date, after:startsAt
- `isActive`: sometimes, required, boolean
- `customerIds`: nullable, array
- `productIds`: nullable, array
- `categoryIds`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/coupons",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/coupons/{coupon}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `couponType` (string) 🔄
- ✅ `value` (number) 
- ✅ `minAmount` (number) 
- ✅ `maxAmount` (number) 
- ✅ `maxUses` (number) 
- ✅ `usedCount` (number) 
- ✅ `startsAt` (datetime) 
- ✅ `expiresAt` (datetime) 
- ✅ `isActive` (boolean) 🔄
- ✅ `customerIds` (array) 
- ✅ `productIds` (array) 
- ✅ `categoryIds` (array) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: sometimes, required, string, max:255, coupons
- `name`: sometimes, required, string, max:255
- `description`: nullable, string
- `couponType`: sometimes, required, string, max:255
- `value`: sometimes, required, numeric, min:0
- `minAmount`: nullable, numeric, min:0
- `maxAmount`: nullable, numeric, min:0
- `maxUses`: nullable, integer, min:1
- `usedCount`: nullable, integer, min:0
- `startsAt`: nullable, date
- `expiresAt`: nullable, date, after:startsAt
- `isActive`: sometimes, required, boolean
- `customerIds`: nullable, array
- `productIds`: nullable, array
- `categoryIds`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/coupons\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "coupons",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/coupons/{coupon}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `couponType` (string) 🔄
- ✅ `value` (number) 
- ✅ `minAmount` (number) 
- ✅ `maxAmount` (number) 
- ✅ `maxUses` (number) 
- ✅ `usedCount` (number) 
- ✅ `startsAt` (datetime) 
- ✅ `expiresAt` (datetime) 
- ✅ `isActive` (boolean) 🔄
- ✅ `customerIds` (array) 
- ✅ `productIds` (array) 
- ✅ `categoryIds` (array) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: sometimes, required, string, max:255, coupons
- `name`: sometimes, required, string, max:255
- `description`: nullable, string
- `couponType`: sometimes, required, string, max:255
- `value`: sometimes, required, numeric, min:0
- `minAmount`: nullable, numeric, min:0
- `maxAmount`: nullable, numeric, min:0
- `maxUses`: nullable, integer, min:1
- `usedCount`: nullable, integer, min:0
- `startsAt`: nullable, date
- `expiresAt`: nullable, date, after:startsAt
- `isActive`: sometimes, required, boolean
- `customerIds`: nullable, array
- `productIds`: nullable, array
- `categoryIds`: nullable, array

---

### 📦 Suppliers

#### `GET` `api/v1/suppliers`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `rfc` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: nullable, email, max:255, suppliers, email
- `phone`: nullable, string, max:20
- `address`: nullable, string, max:500
- `rfc`: nullable, string, max:13, suppliers, rfc
- `isActive`: sometimes, boolean

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

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `rfc` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: nullable, email, max:255, suppliers, email
- `phone`: nullable, string, max:20
- `address`: nullable, string, max:500
- `rfc`: nullable, string, max:13, suppliers, rfc
- `isActive`: sometimes, boolean

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/suppliers/{supplier}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `rfc` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: nullable, email, max:255, suppliers, email
- `phone`: nullable, string, max:20
- `address`: nullable, string, max:500
- `rfc`: nullable, string, max:13, suppliers, rfc
- `isActive`: sometimes, boolean

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

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `rfc` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: nullable, email, max:255, suppliers, email
- `phone`: nullable, string, max:20
- `address`: nullable, string, max:500
- `rfc`: nullable, string, max:13, suppliers, rfc
- `isActive`: sometimes, boolean

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/suppliers\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "suppliers",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/suppliers/{supplier}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `rfc` (string) 
- ✅ `isActive` (boolean) 🔄
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: nullable, email, max:255, suppliers, email
- `phone`: nullable, string, max:20
- `address`: nullable, string, max:500
- `rfc`: nullable, string, max:13, suppliers, rfc
- `isActive`: sometimes, boolean

---

### 📦 Purchase orders

#### `GET` `api/v1/purchase-orders`

**Campos disponibles:**

- ✅ `supplierId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `supplier` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `supplier`: required, sometimes

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

- ✅ `supplierId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `supplier` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `supplier`: required, sometimes

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/purchase-orders/{purchase_order}`

**Campos disponibles:**

- ✅ `supplierId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `supplier` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `supplier`: required, sometimes

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

- ✅ `supplierId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `supplier` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `supplier`: required, sometimes

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/purchase_orders\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "purchase_orders",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/purchase-orders/{purchase_order}`

**Campos disponibles:**

- ✅ `supplierId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `supplier` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `supplier`: required, sometimes

---

### 📦 Purchase order items

#### `GET` `api/v1/purchase-order-items`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

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
- ✅ `unitPrice` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

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
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/purchase-order-items/{purchase_order_item}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

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
- ✅ `unitPrice` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/purchase_order_items\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "purchase_order_items",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/purchase-order-items/{purchase_order_item}`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `unitPrice` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

---

### 📦 Customers

#### `GET` `api/v1/customers`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `city` (string) 
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `classification` (string) 🔄
- ✅ `credit_limit` (number) 
- ✅ `current_credit` (number) 
- ✅ `is_active` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔄
- ✅ `updated_at` (datetime) 🔄

**Relaciones disponibles:**

- `salesOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, customers, email
- `phone`: nullable, string, max:50
- `address`: nullable, string, max:255
- `city`: nullable, string, max:100
- `state`: nullable, string, max:100
- `country`: nullable, string, max:100
- `classification`: required, mayorista, minorista, especial
- `credit_limit`: nullable, numeric, min:0
- `current_credit`: nullable, numeric, min:0
- `is_active`: boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/customers",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/customers`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `city` (string) 
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `classification` (string) 🔄
- ✅ `credit_limit` (number) 
- ✅ `current_credit` (number) 
- ✅ `is_active` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔄
- ✅ `updated_at` (datetime) 🔄

**Relaciones disponibles:**

- `salesOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, customers, email
- `phone`: nullable, string, max:50
- `address`: nullable, string, max:255
- `city`: nullable, string, max:100
- `state`: nullable, string, max:100
- `country`: nullable, string, max:100
- `classification`: required, mayorista, minorista, especial
- `credit_limit`: nullable, numeric, min:0
- `current_credit`: nullable, numeric, min:0
- `is_active`: boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/customers",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "customers",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/customers/{customer}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `city` (string) 
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `classification` (string) 🔄
- ✅ `credit_limit` (number) 
- ✅ `current_credit` (number) 
- ✅ `is_active` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔄
- ✅ `updated_at` (datetime) 🔄

**Relaciones disponibles:**

- `salesOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, customers, email
- `phone`: nullable, string, max:50
- `address`: nullable, string, max:255
- `city`: nullable, string, max:100
- `state`: nullable, string, max:100
- `country`: nullable, string, max:100
- `classification`: required, mayorista, minorista, especial
- `credit_limit`: nullable, numeric, min:0
- `current_credit`: nullable, numeric, min:0
- `is_active`: boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/customers",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/customers/{customer}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `city` (string) 
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `classification` (string) 🔄
- ✅ `credit_limit` (number) 
- ✅ `current_credit` (number) 
- ✅ `is_active` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔄
- ✅ `updated_at` (datetime) 🔄

**Relaciones disponibles:**

- `salesOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, customers, email
- `phone`: nullable, string, max:50
- `address`: nullable, string, max:255
- `city`: nullable, string, max:100
- `state`: nullable, string, max:100
- `country`: nullable, string, max:100
- `classification`: required, mayorista, minorista, especial
- `credit_limit`: nullable, numeric, min:0
- `current_credit`: nullable, numeric, min:0
- `is_active`: boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/customers\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "customers",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/customers/{customer}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `email` (string) 🔄
- ✅ `phone` (string) 
- ✅ `address` (string) 
- ✅ `city` (string) 
- ✅ `state` (string) 
- ✅ `country` (string) 
- ✅ `classification` (string) 🔄
- ✅ `credit_limit` (number) 
- ✅ `current_credit` (number) 
- ✅ `is_active` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔄
- ✅ `updated_at` (datetime) 🔄

**Relaciones disponibles:**

- `salesOrders` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, customers, email
- `phone`: nullable, string, max:50
- `address`: nullable, string, max:255
- `city`: nullable, string, max:100
- `state`: nullable, string, max:100
- `country`: nullable, string, max:100
- `classification`: required, mayorista, minorista, especial
- `credit_limit`: nullable, numeric, min:0
- `current_credit`: nullable, numeric, min:0
- `is_active`: boolean
- `metadata`: nullable, array

---

### 📦 Sales orders

#### `GET` `api/v1/sales-orders`

**Campos disponibles:**

- ✅ `customer_id` (number) 
- ✅ `order_number` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `order_date` (datetime) 🔄
- ✅ `approved_at` (datetime) 🔄
- ✅ `delivered_at` (datetime) 🔄
- ✅ `subtotal_amount` (number) 🔄
- ✅ `tax_amount` (number) 
- ✅ `discount_total` (number) 
- ✅ `total_amount` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒🔄

**Relaciones disponibles:**

- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `customer_id`: required, exists:customers,id
- `order_number`: required, string, max:50, sales_orders, order_number
- `status`: required, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/sales_orders",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/sales-orders`

**Campos disponibles:**

- ✅ `customer_id` (number) 
- ✅ `order_number` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `order_date` (datetime) 🔄
- ✅ `approved_at` (datetime) 🔄
- ✅ `delivered_at` (datetime) 🔄
- ✅ `subtotal_amount` (number) 🔄
- ✅ `tax_amount` (number) 
- ✅ `discount_total` (number) 
- ✅ `total_amount` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒🔄

**Relaciones disponibles:**

- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `customer_id`: required, exists:customers,id
- `order_number`: required, string, max:50, sales_orders, order_number
- `status`: required, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/sales_orders",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "sales_orders",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/sales-orders/{sales_order}`

**Campos disponibles:**

- ✅ `customer_id` (number) 
- ✅ `order_number` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `order_date` (datetime) 🔄
- ✅ `approved_at` (datetime) 🔄
- ✅ `delivered_at` (datetime) 🔄
- ✅ `subtotal_amount` (number) 🔄
- ✅ `tax_amount` (number) 
- ✅ `discount_total` (number) 
- ✅ `total_amount` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒🔄

**Relaciones disponibles:**

- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `customer_id`: required, exists:customers,id
- `order_number`: required, string, max:50, sales_orders, order_number
- `status`: required, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/sales_orders",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/sales-orders/{sales_order}`

**Campos disponibles:**

- ✅ `customer_id` (number) 
- ✅ `order_number` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `order_date` (datetime) 🔄
- ✅ `approved_at` (datetime) 🔄
- ✅ `delivered_at` (datetime) 🔄
- ✅ `subtotal_amount` (number) 🔄
- ✅ `tax_amount` (number) 
- ✅ `discount_total` (number) 
- ✅ `total_amount` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒🔄

**Relaciones disponibles:**

- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `customer_id`: required, exists:customers,id
- `order_number`: required, string, max:50, sales_orders, order_number
- `status`: required, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/sales_orders\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "sales_orders",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/sales-orders/{sales_order}`

**Campos disponibles:**

- ✅ `customer_id` (number) 
- ✅ `order_number` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `order_date` (datetime) 🔄
- ✅ `approved_at` (datetime) 🔄
- ✅ `delivered_at` (datetime) 🔄
- ✅ `subtotal_amount` (number) 🔄
- ✅ `tax_amount` (number) 
- ✅ `discount_total` (number) 
- ✅ `total_amount` (number) 🔄
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒🔄

**Relaciones disponibles:**

- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `customer_id`: required, exists:customers,id
- `order_number`: required, string, max:50, sales_orders, order_number
- `status`: required, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

---

### 📦 Sales order items

#### `GET` `api/v1/sales-order-items`

**Campos disponibles:**

- ✅ `salesOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `salesOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `salesOrderId`: required, integer, exists:sales_orders,id
- `productId`: required, integer, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/sales_order_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/sales-order-items`

**Campos disponibles:**

- ✅ `salesOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `salesOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `salesOrderId`: required, integer, exists:sales_orders,id
- `productId`: required, integer, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/sales_order_items",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "sales_order_items",
            "attributes": {
                "title": "Nueva p\u00e1gina",
                "slug": "nueva-pagina",
                "html": "<h1>Contenido HTML<\/h1>",
                "css": "h1 { color: blue; }",
                "json": {
                    "component": "header"
                },
                "status": "draft"
            },
            "relationships": {
                "user": {
                    "data": {
                        "type": "users",
                        "id": "1"
                    }
                }
            }
        }
    }
}
```

---

#### `GET` `api/v1/sales-order-items/{sales_order_item}`

**Campos disponibles:**

- ✅ `salesOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `salesOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `salesOrderId`: required, integer, exists:sales_orders,id
- `productId`: required, integer, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/sales_order_items",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/sales-order-items/{sales_order_item}`

**Campos disponibles:**

- ✅ `salesOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `salesOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `salesOrderId`: required, integer, exists:sales_orders,id
- `productId`: required, integer, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/sales_order_items\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "sales_order_items",
            "id": "1",
            "attributes": {
                "status": "published",
                "title": "T\u00edtulo actualizado"
            }
        }
    }
}
```

---

#### `DELETE` `api/v1/sales-order-items/{sales_order_item}`

**Campos disponibles:**

- ✅ `salesOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `salesOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `salesOrderId`: required, integer, exists:sales_orders,id
- `productId`: required, integer, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `total`: required, numeric, min:0
- `metadata`: nullable, array

---

