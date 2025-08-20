# API Documentation

**Generado:** 2025-08-20T11:01:56.241297Z

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

- `description`: nullable, string
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

- `description`: nullable, string
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

- `description`: nullable, string
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

- `description`: nullable, string
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

- `description`: nullable, string
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

- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array

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

- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array

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

- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array

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

- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array

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

- `reservedQuantity`: sometimes, nullable, numeric, min:0
- `minimumStock`: sometimes, nullable, numeric, min:0
- `maximumStock`: sometimes, nullable, numeric, min:0
- `reorderPoint`: sometimes, nullable, numeric, min:0
- `lastMovementDate`: sometimes, nullable, date
- `lastMovementType`: sometimes, nullable, string, in:in,out,adjustment,transfer
- `batchInfo`: sometimes, nullable, array
- `metadata`: sometimes, nullable, array

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

### 📦 Accounts

#### `GET` `api/v1/accounts`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `level` (number) 🔄
- ✅ `parentId` (number) 
- ✅ `currency` (string) 🔄
- ✅ `isPostable` (boolean) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, accounts
- `name`: required, string, max:255
- `accountType`: required, string, max:255
- `level`: required, integer
- `parentId`: nullable, string
- `currency`: nullable, string, max:255
- `isPostable`: required, boolean
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/accounts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/accounts`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `level` (number) 🔄
- ✅ `parentId` (number) 
- ✅ `currency` (string) 🔄
- ✅ `isPostable` (boolean) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, accounts
- `name`: required, string, max:255
- `accountType`: required, string, max:255
- `level`: required, integer
- `parentId`: nullable, string
- `currency`: nullable, string, max:255
- `isPostable`: required, boolean
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/accounts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "accounts",
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

#### `GET` `api/v1/accounts/{account}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `level` (number) 🔄
- ✅ `parentId` (number) 
- ✅ `currency` (string) 🔄
- ✅ `isPostable` (boolean) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, accounts
- `name`: required, string, max:255
- `accountType`: required, string, max:255
- `level`: required, integer
- `parentId`: nullable, string
- `currency`: nullable, string, max:255
- `isPostable`: required, boolean
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/accounts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/accounts/{account}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `level` (number) 🔄
- ✅ `parentId` (number) 
- ✅ `currency` (string) 🔄
- ✅ `isPostable` (boolean) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, accounts
- `name`: required, string, max:255
- `accountType`: required, string, max:255
- `level`: required, integer
- `parentId`: nullable, string
- `currency`: nullable, string, max:255
- `isPostable`: required, boolean
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/accounts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "accounts",
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

#### `DELETE` `api/v1/accounts/{account}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `level` (number) 🔄
- ✅ `parentId` (number) 
- ✅ `currency` (string) 🔄
- ✅ `isPostable` (boolean) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, accounts
- `name`: required, string, max:255
- `accountType`: required, string, max:255
- `level`: required, integer
- `parentId`: nullable, string
- `currency`: nullable, string, max:255
- `isPostable`: required, boolean
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 Fiscal periods

#### `GET` `api/v1/fiscal-periods`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `startDate` (datetime) 🔄
- ✅ `endDate` (datetime) 🔄
- ✅ `status` (string) 🔄
- ✅ `allowBackpost` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255, fiscal_periods
- `start_date`: required, date
- `end_date`: required, date
- `status`: required, string, max:255
- `allow_backpost`: required, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/fiscal_periods",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/fiscal-periods`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `startDate` (datetime) 🔄
- ✅ `endDate` (datetime) 🔄
- ✅ `status` (string) 🔄
- ✅ `allowBackpost` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255, fiscal_periods
- `start_date`: required, date
- `end_date`: required, date
- `status`: required, string, max:255
- `allow_backpost`: required, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/fiscal_periods",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "fiscal_periods",
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

#### `GET` `api/v1/fiscal-periods/{fiscal_period}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `startDate` (datetime) 🔄
- ✅ `endDate` (datetime) 🔄
- ✅ `status` (string) 🔄
- ✅ `allowBackpost` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255, fiscal_periods
- `start_date`: required, date
- `end_date`: required, date
- `status`: required, string, max:255
- `allow_backpost`: required, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/fiscal_periods",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/fiscal-periods/{fiscal_period}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `startDate` (datetime) 🔄
- ✅ `endDate` (datetime) 🔄
- ✅ `status` (string) 🔄
- ✅ `allowBackpost` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255, fiscal_periods
- `start_date`: required, date
- `end_date`: required, date
- `status`: required, string, max:255
- `allow_backpost`: required, boolean
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/fiscal_periods\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "fiscal_periods",
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

#### `DELETE` `api/v1/fiscal-periods/{fiscal_period}`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `startDate` (datetime) 🔄
- ✅ `endDate` (datetime) 🔄
- ✅ `status` (string) 🔄
- ✅ `allowBackpost` (boolean) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255, fiscal_periods
- `start_date`: required, date
- `end_date`: required, date
- `status`: required, string, max:255
- `allow_backpost`: required, boolean
- `metadata`: nullable, array

---

### 📦 Journals

#### `GET` `api/v1/journals`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `autoNumbering` (boolean) 🔄
- ✅ `sequencePrefix` (string) 🔄
- ✅ `sequenceNext` (number) 🔄
- ✅ `defaultCurrency` (string) 🔄
- ✅ `postPolicy` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, journals
- `name`: required, string, max:255
- `auto_numbering`: required, boolean
- `sequence_prefix`: nullable, string, max:255
- `sequence_next`: required, integer
- `default_currency`: nullable, string, max:255
- `post_policy`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journals",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/journals`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `autoNumbering` (boolean) 🔄
- ✅ `sequencePrefix` (string) 🔄
- ✅ `sequenceNext` (number) 🔄
- ✅ `defaultCurrency` (string) 🔄
- ✅ `postPolicy` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, journals
- `name`: required, string, max:255
- `auto_numbering`: required, boolean
- `sequence_prefix`: nullable, string, max:255
- `sequence_next`: required, integer
- `default_currency`: nullable, string, max:255
- `post_policy`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/journals",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journals",
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

#### `GET` `api/v1/journals/{journal}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `autoNumbering` (boolean) 🔄
- ✅ `sequencePrefix` (string) 🔄
- ✅ `sequenceNext` (number) 🔄
- ✅ `defaultCurrency` (string) 🔄
- ✅ `postPolicy` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, journals
- `name`: required, string, max:255
- `auto_numbering`: required, boolean
- `sequence_prefix`: nullable, string, max:255
- `sequence_next`: required, integer
- `default_currency`: nullable, string, max:255
- `post_policy`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journals",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/journals/{journal}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `autoNumbering` (boolean) 🔄
- ✅ `sequencePrefix` (string) 🔄
- ✅ `sequenceNext` (number) 🔄
- ✅ `defaultCurrency` (string) 🔄
- ✅ `postPolicy` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, journals
- `name`: required, string, max:255
- `auto_numbering`: required, boolean
- `sequence_prefix`: nullable, string, max:255
- `sequence_next`: required, integer
- `default_currency`: nullable, string, max:255
- `post_policy`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/journals\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journals",
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

#### `DELETE` `api/v1/journals/{journal}`

**Campos disponibles:**

- ✅ `code` (string) 🔄
- ✅ `name` (string) 🔄
- ✅ `autoNumbering` (boolean) 🔄
- ✅ `sequencePrefix` (string) 🔄
- ✅ `sequenceNext` (number) 🔄
- ✅ `defaultCurrency` (string) 🔄
- ✅ `postPolicy` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `code`: required, string, max:255, journals
- `name`: required, string, max:255
- `auto_numbering`: required, boolean
- `sequence_prefix`: nullable, string, max:255
- `sequence_next`: required, integer
- `default_currency`: nullable, string, max:255
- `post_policy`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 Journal entries

#### `GET` `api/v1/journal-entries`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journal_entries",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/journal-entries`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/journal_entries",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journal_entries",
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

#### `GET` `api/v1/journal-entries/{journal_entry}`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journal_entries",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/journal-entries/{journal_entry}`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/journal_entries\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journal_entries",
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

#### `DELETE` `api/v1/journal-entries/{journal_entry}`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

---

#### `POST` `api/v1/journal-entries/{journal_entry}/post`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/journal_entries",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journal_entries",
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

#### `GET` `api/v1/journal-entries/{journal_entry}/totals`

**Campos disponibles:**

- ✅ `journalId` (number) 
- ✅ `periodId` (number) 
- ✅ `number` (string) 🔄
- ✅ `date` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `reference` (string) 🔄
- ✅ `description` (string) 
- ✅ `status` (string) 🔄
- ✅ `approvedById` (number) 
- ✅ `postedById` (number) 
- ✅ `postedAt` (datetime) 🔄
- ✅ `reversalOfId` (number) 
- ✅ `sourceType` (string) 🔄
- ✅ `sourceId` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_id`: required, string
- `period_id`: required, string
- `number`: nullable, string, max:255, journal_entries
- `date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `reference`: nullable, string, max:255
- `description`: nullable, string
- `status`: required, string, max:255
- `approved_by_id`: nullable, string
- `posted_by_id`: nullable, string
- `posted_at`: nullable, string
- `reversal_of_id`: nullable, string
- `source_type`: nullable, string, max:255
- `source_id`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journal_entries",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

### 📦 Journal lines

#### `GET` `api/v1/journal-lines`

**Campos disponibles:**

- ✅ `journalEntryId` (number) 
- ✅ `accountId` (number) 
- ✅ `debit` (number) 🔄
- ✅ `credit` (number) 🔄
- ✅ `baseAmount` (number) 🔄
- ✅ `costCenterId` (number) 
- ✅ `partnerId` (number) 
- ✅ `memo` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_entry_id`: required, string
- `account_id`: required, string
- `debit`: required, string
- `credit`: required, string
- `base_amount`: nullable, string
- `cost_center_id`: nullable, string
- `partner_id`: nullable, string
- `memo`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journal_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/journal-lines`

**Campos disponibles:**

- ✅ `journalEntryId` (number) 
- ✅ `accountId` (number) 
- ✅ `debit` (number) 🔄
- ✅ `credit` (number) 🔄
- ✅ `baseAmount` (number) 🔄
- ✅ `costCenterId` (number) 
- ✅ `partnerId` (number) 
- ✅ `memo` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_entry_id`: required, string
- `account_id`: required, string
- `debit`: required, string
- `credit`: required, string
- `base_amount`: nullable, string
- `cost_center_id`: nullable, string
- `partner_id`: nullable, string
- `memo`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/journal_lines",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journal_lines",
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

#### `GET` `api/v1/journal-lines/{journal_line}`

**Campos disponibles:**

- ✅ `journalEntryId` (number) 
- ✅ `accountId` (number) 
- ✅ `debit` (number) 🔄
- ✅ `credit` (number) 🔄
- ✅ `baseAmount` (number) 🔄
- ✅ `costCenterId` (number) 
- ✅ `partnerId` (number) 
- ✅ `memo` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_entry_id`: required, string
- `account_id`: required, string
- `debit`: required, string
- `credit`: required, string
- `base_amount`: nullable, string
- `cost_center_id`: nullable, string
- `partner_id`: nullable, string
- `memo`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/journal_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/journal-lines/{journal_line}`

**Campos disponibles:**

- ✅ `journalEntryId` (number) 
- ✅ `accountId` (number) 
- ✅ `debit` (number) 🔄
- ✅ `credit` (number) 🔄
- ✅ `baseAmount` (number) 🔄
- ✅ `costCenterId` (number) 
- ✅ `partnerId` (number) 
- ✅ `memo` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_entry_id`: required, string
- `account_id`: required, string
- `debit`: required, string
- `credit`: required, string
- `base_amount`: nullable, string
- `cost_center_id`: nullable, string
- `partner_id`: nullable, string
- `memo`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/journal_lines\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "journal_lines",
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

#### `DELETE` `api/v1/journal-lines/{journal_line}`

**Campos disponibles:**

- ✅ `journalEntryId` (number) 
- ✅ `accountId` (number) 
- ✅ `debit` (number) 🔄
- ✅ `credit` (number) 🔄
- ✅ `baseAmount` (number) 🔄
- ✅ `costCenterId` (number) 
- ✅ `partnerId` (number) 
- ✅ `memo` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `journal_entry_id`: required, string
- `account_id`: required, string
- `debit`: required, string
- `credit`: required, string
- `base_amount`: nullable, string
- `cost_center_id`: nullable, string
- `partner_id`: nullable, string
- `memo`: nullable, string, max:255
- `metadata`: nullable, array

---

### 📦 Exchange rates

#### `GET` `api/v1/exchange-rates`

**Campos disponibles:**

- ✅ `baseCurrency` (string) 🔄
- ✅ `quoteCurrency` (string) 🔄
- ✅ `rateDate` (datetime) 🔄
- ✅ `rate` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `base_currency`: required, string, max:255
- `quote_currency`: required, string, max:255
- `rate_date`: required, date
- `rate`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/exchange_rates",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/exchange-rates`

**Campos disponibles:**

- ✅ `baseCurrency` (string) 🔄
- ✅ `quoteCurrency` (string) 🔄
- ✅ `rateDate` (datetime) 🔄
- ✅ `rate` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `base_currency`: required, string, max:255
- `quote_currency`: required, string, max:255
- `rate_date`: required, date
- `rate`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/exchange_rates",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "exchange_rates",
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

#### `GET` `api/v1/exchange-rates/{exchange_rate}`

**Campos disponibles:**

- ✅ `baseCurrency` (string) 🔄
- ✅ `quoteCurrency` (string) 🔄
- ✅ `rateDate` (datetime) 🔄
- ✅ `rate` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `base_currency`: required, string, max:255
- `quote_currency`: required, string, max:255
- `rate_date`: required, date
- `rate`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/exchange_rates",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/exchange-rates/{exchange_rate}`

**Campos disponibles:**

- ✅ `baseCurrency` (string) 🔄
- ✅ `quoteCurrency` (string) 🔄
- ✅ `rateDate` (datetime) 🔄
- ✅ `rate` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `base_currency`: required, string, max:255
- `quote_currency`: required, string, max:255
- `rate_date`: required, date
- `rate`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/exchange_rates\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "exchange_rates",
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

#### `DELETE` `api/v1/exchange-rates/{exchange_rate}`

**Campos disponibles:**

- ✅ `baseCurrency` (string) 🔄
- ✅ `quoteCurrency` (string) 🔄
- ✅ `rateDate` (datetime) 🔄
- ✅ `rate` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `base_currency`: required, string, max:255
- `quote_currency`: required, string, max:255
- `rate_date`: required, date
- `rate`: required, string
- `metadata`: nullable, array

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
- ✅ `filePath` (string) 
- ✅ `originalFilename` (string) 
- ✅ `mimeType` (string) 
- ✅ `fileSize` (number) 
- ✅ `uploadedBy` (number) 
- ✅ `verifiedAt` (datetime) 
- ✅ `verifiedBy` (number) 
- ✅ `expiresAt` (datetime) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255, rfc, cedula_fiscal, ine, constancia_sat, opinion_sat, certificado_sello, comprobante_domicilio, cotizacion, orden_compra, factura, contrato, otros
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
- ✅ `filePath` (string) 
- ✅ `originalFilename` (string) 
- ✅ `mimeType` (string) 
- ✅ `fileSize` (number) 
- ✅ `uploadedBy` (number) 
- ✅ `verifiedAt` (datetime) 
- ✅ `verifiedBy` (number) 
- ✅ `expiresAt` (datetime) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255, rfc, cedula_fiscal, ine, constancia_sat, opinion_sat, certificado_sello, comprobante_domicilio, cotizacion, orden_compra, factura, contrato, otros
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
- ✅ `filePath` (string) 
- ✅ `originalFilename` (string) 
- ✅ `mimeType` (string) 
- ✅ `fileSize` (number) 
- ✅ `uploadedBy` (number) 
- ✅ `verifiedAt` (datetime) 
- ✅ `verifiedBy` (number) 
- ✅ `expiresAt` (datetime) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255, rfc, cedula_fiscal, ine, constancia_sat, opinion_sat, certificado_sello, comprobante_domicilio, cotizacion, orden_compra, factura, contrato, otros
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
- ✅ `filePath` (string) 
- ✅ `originalFilename` (string) 
- ✅ `mimeType` (string) 
- ✅ `fileSize` (number) 
- ✅ `uploadedBy` (number) 
- ✅ `verifiedAt` (datetime) 
- ✅ `verifiedBy` (number) 
- ✅ `expiresAt` (datetime) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255, rfc, cedula_fiscal, ine, constancia_sat, opinion_sat, certificado_sello, comprobante_domicilio, cotizacion, orden_compra, factura, contrato, otros
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
- ✅ `filePath` (string) 
- ✅ `originalFilename` (string) 
- ✅ `mimeType` (string) 
- ✅ `fileSize` (number) 
- ✅ `uploadedBy` (number) 
- ✅ `verifiedAt` (datetime) 
- ✅ `verifiedBy` (number) 
- ✅ `expiresAt` (datetime) 
- ✅ `notes` (string) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒

**Relaciones disponibles:**

- `contact` (relationship)

**Validaciones:**

- `contact_id`: nullable, integer
- `document_type`: nullable, string, max:255, rfc, cedula_fiscal, ine, constancia_sat, opinion_sat, certificado_sello, comprobante_domicilio, cotizacion, orden_compra, factura, contrato, otros
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

#### `POST` `api/v1/contact-documents/upload`

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

#### `GET` `api/v1/contact-documents/{document}/download`

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

#### `GET` `api/v1/contact-documents/{document}/view`

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

#### `PATCH` `api/v1/contact-documents/{document}/verify`

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

#### `PATCH` `api/v1/contact-documents/{document}/unverify`

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

- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `isPrimary`: nullable, boolean
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

- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `isPrimary`: nullable, boolean
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

- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `isPrimary`: nullable, boolean
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

- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `isPrimary`: nullable, boolean
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

- `position`: nullable, string, max:255
- `department`: nullable, string, max:255
- `email`: nullable, string, max:255, email
- `phone`: nullable, string, max:255
- `mobile`: nullable, string, max:255
- `isPrimary`: nullable, boolean
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

### 📦 Bank accounts

#### `GET` `api/v1/bank-accounts`

**Campos disponibles:**

- ✅ `bankName` (string) 🔄
- ✅ `accountNumber` (string) 🔄
- ✅ `clabe` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `openingBalance` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bankName`: required, string, max:255
- `accountNumber`: required, string, max:255, bank_accounts, account_number
- `clabe`: nullable, string, max:255
- `currency`: required, string, max:255
- `accountType`: required, string, max:255
- `openingBalance`: required, numeric
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_accounts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/bank-accounts`

**Campos disponibles:**

- ✅ `bankName` (string) 🔄
- ✅ `accountNumber` (string) 🔄
- ✅ `clabe` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `openingBalance` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bankName`: required, string, max:255
- `accountNumber`: required, string, max:255, bank_accounts, account_number
- `clabe`: nullable, string, max:255
- `currency`: required, string, max:255
- `accountType`: required, string, max:255
- `openingBalance`: required, numeric
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/bank_accounts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_accounts",
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

#### `GET` `api/v1/bank-accounts/{bank_account}`

**Campos disponibles:**

- ✅ `bankName` (string) 🔄
- ✅ `accountNumber` (string) 🔄
- ✅ `clabe` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `openingBalance` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bankName`: required, string, max:255
- `accountNumber`: required, string, max:255, bank_accounts, account_number
- `clabe`: nullable, string, max:255
- `currency`: required, string, max:255
- `accountType`: required, string, max:255
- `openingBalance`: required, numeric
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_accounts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/bank-accounts/{bank_account}`

**Campos disponibles:**

- ✅ `bankName` (string) 🔄
- ✅ `accountNumber` (string) 🔄
- ✅ `clabe` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `openingBalance` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bankName`: required, string, max:255
- `accountNumber`: required, string, max:255, bank_accounts, account_number
- `clabe`: nullable, string, max:255
- `currency`: required, string, max:255
- `accountType`: required, string, max:255
- `openingBalance`: required, numeric
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/bank_accounts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_accounts",
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

#### `DELETE` `api/v1/bank-accounts/{bank_account}`

**Campos disponibles:**

- ✅ `bankName` (string) 🔄
- ✅ `accountNumber` (string) 🔄
- ✅ `clabe` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `accountType` (string) 🔄
- ✅ `openingBalance` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bankName`: required, string, max:255
- `accountNumber`: required, string, max:255, bank_accounts, account_number
- `clabe`: nullable, string, max:255
- `currency`: required, string, max:255
- `accountType`: required, string, max:255
- `openingBalance`: required, numeric
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 Bank statements

#### `GET` `api/v1/bank-statements`

**Campos disponibles:**

- ✅ `bankAccountId` (number) 
- ✅ `statementDate` (datetime) 🔄
- ✅ `importSource` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_account_id`: required, string
- `statement_date`: required, date
- `import_source`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_statements",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/bank-statements`

**Campos disponibles:**

- ✅ `bankAccountId` (number) 
- ✅ `statementDate` (datetime) 🔄
- ✅ `importSource` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_account_id`: required, string
- `statement_date`: required, date
- `import_source`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/bank_statements",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_statements",
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

#### `GET` `api/v1/bank-statements/{bank_statement}`

**Campos disponibles:**

- ✅ `bankAccountId` (number) 
- ✅ `statementDate` (datetime) 🔄
- ✅ `importSource` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_account_id`: required, string
- `statement_date`: required, date
- `import_source`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_statements",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/bank-statements/{bank_statement}`

**Campos disponibles:**

- ✅ `bankAccountId` (number) 
- ✅ `statementDate` (datetime) 🔄
- ✅ `importSource` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_account_id`: required, string
- `statement_date`: required, date
- `import_source`: nullable, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/bank_statements\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_statements",
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

#### `DELETE` `api/v1/bank-statements/{bank_statement}`

**Campos disponibles:**

- ✅ `bankAccountId` (number) 
- ✅ `statementDate` (datetime) 🔄
- ✅ `importSource` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_account_id`: required, string
- `statement_date`: required, date
- `import_source`: nullable, string, max:255
- `metadata`: nullable, array

---

### 📦 Bank statement lines

#### `GET` `api/v1/bank-statement-lines`

**Campos disponibles:**

- ✅ `bankStatementId` (number) 
- ✅ `txnDate` (datetime) 🔄
- ✅ `amount` (number) 🔄
- ✅ `counterparty` (string) 🔄
- ✅ `reference` (string) 🔄
- ✅ `fitid` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_statement_id`: required, string
- `txn_date`: required, date
- `amount`: required, string
- `counterparty`: nullable, string, max:255
- `reference`: nullable, string, max:255
- `fitid`: nullable, string, max:255
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_statement_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/bank-statement-lines`

**Campos disponibles:**

- ✅ `bankStatementId` (number) 
- ✅ `txnDate` (datetime) 🔄
- ✅ `amount` (number) 🔄
- ✅ `counterparty` (string) 🔄
- ✅ `reference` (string) 🔄
- ✅ `fitid` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_statement_id`: required, string
- `txn_date`: required, date
- `amount`: required, string
- `counterparty`: nullable, string, max:255
- `reference`: nullable, string, max:255
- `fitid`: nullable, string, max:255
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/bank_statement_lines",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_statement_lines",
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

#### `GET` `api/v1/bank-statement-lines/{bank_statement_line}`

**Campos disponibles:**

- ✅ `bankStatementId` (number) 
- ✅ `txnDate` (datetime) 🔄
- ✅ `amount` (number) 🔄
- ✅ `counterparty` (string) 🔄
- ✅ `reference` (string) 🔄
- ✅ `fitid` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_statement_id`: required, string
- `txn_date`: required, date
- `amount`: required, string
- `counterparty`: nullable, string, max:255
- `reference`: nullable, string, max:255
- `fitid`: nullable, string, max:255
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/bank_statement_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/bank-statement-lines/{bank_statement_line}`

**Campos disponibles:**

- ✅ `bankStatementId` (number) 
- ✅ `txnDate` (datetime) 🔄
- ✅ `amount` (number) 🔄
- ✅ `counterparty` (string) 🔄
- ✅ `reference` (string) 🔄
- ✅ `fitid` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_statement_id`: required, string
- `txn_date`: required, date
- `amount`: required, string
- `counterparty`: nullable, string, max:255
- `reference`: nullable, string, max:255
- `fitid`: nullable, string, max:255
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/bank_statement_lines\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "bank_statement_lines",
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

#### `DELETE` `api/v1/bank-statement-lines/{bank_statement_line}`

**Campos disponibles:**

- ✅ `bankStatementId` (number) 
- ✅ `txnDate` (datetime) 🔄
- ✅ `amount` (number) 🔄
- ✅ `counterparty` (string) 🔄
- ✅ `reference` (string) 🔄
- ✅ `fitid` (string) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `bank_statement_id`: required, string
- `txn_date`: required, date
- `amount`: required, string
- `counterparty`: nullable, string, max:255
- `reference`: nullable, string, max:255
- `fitid`: nullable, string, max:255
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 A p invoices

#### `GET` `api/v1/a-p-invoices`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoices",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-p-invoices`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_p_invoices",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoices",
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

#### `GET` `api/v1/a-p-invoices/{a_p_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoices",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-p-invoices/{a_p_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_p_invoices\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoices",
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

#### `DELETE` `api/v1/a-p-invoices/{a_p_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 A p invoice lines

#### `GET` `api/v1/a-p-invoice-lines`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoice_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-p-invoice-lines`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_p_invoice_lines",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoice_lines",
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

#### `GET` `api/v1/a-p-invoice-lines/{a_p_invoice_line}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoice_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-p-invoice-lines/{a_p_invoice_line}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_p_invoice_lines\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoice_lines",
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

#### `DELETE` `api/v1/a-p-invoice-lines/{a_p_invoice_line}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

---

### 📦 A p payments

#### `GET` `api/v1/a-p-payments`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `paymentDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `payment_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_payments",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-p-payments`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `paymentDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `payment_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_p_payments",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_payments",
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

#### `GET` `api/v1/a-p-payments/{a_p_payment}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `paymentDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `payment_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_payments",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-p-payments/{a_p_payment}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `paymentDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `payment_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_p_payments\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_payments",
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

#### `DELETE` `api/v1/a-p-payments/{a_p_payment}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `paymentDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `payment_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 A p invoice payments

#### `GET` `api/v1/a-p-invoice-payments`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `apPaymentId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `ap_payment_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoice_payments",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-p-invoice-payments`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `apPaymentId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `ap_payment_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_p_invoice_payments",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoice_payments",
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

#### `GET` `api/v1/a-p-invoice-payments/{a_p_invoice_payment}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `apPaymentId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `ap_payment_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_p_invoice_payments",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-p-invoice-payments/{a_p_invoice_payment}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `apPaymentId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `ap_payment_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_p_invoice_payments\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_p_invoice_payments",
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

#### `DELETE` `api/v1/a-p-invoice-payments/{a_p_invoice_payment}`

**Campos disponibles:**

- ✅ `apInvoiceId` (number) 
- ✅ `apPaymentId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ap_invoice_id`: required, string
- `ap_payment_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

---

### 📦 A r invoices

#### `GET` `api/v1/a-r-invoices`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoices",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-r-invoices`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_r_invoices",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoices",
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

#### `GET` `api/v1/a-r-invoices/{a_r_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoices",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-r-invoices/{a_r_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_r_invoices\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoices",
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

#### `DELETE` `api/v1/a-r-invoices/{a_r_invoice}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `invoiceNumber` (string) 🔄
- ✅ `invoiceDate` (datetime) 🔄
- ✅ `dueDate` (datetime) 🔄
- ✅ `currency` (string) 🔄
- ✅ `exchangeRate` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `taxTotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `invoice_number`: required, string, max:255
- `invoice_date`: required, date
- `due_date`: required, date
- `currency`: nullable, string, max:255
- `exchange_rate`: nullable, string
- `subtotal`: required, string
- `tax_total`: required, string
- `total`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 A r invoice lines

#### `GET` `api/v1/a-r-invoice-lines`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoice_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-r-invoice-lines`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_r_invoice_lines",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoice_lines",
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

#### `GET` `api/v1/a-r-invoice-lines/{a_r_invoice_line}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoice_lines",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-r-invoice-lines/{a_r_invoice_line}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_r_invoice_lines\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoice_lines",
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

#### `DELETE` `api/v1/a-r-invoice-lines/{a_r_invoice_line}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `description` (string) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `lineTotal` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `description`: required, string, max:255
- `quantity`: required, string
- `unit_price`: required, string
- `discount`: required, string
- `line_total`: required, string
- `metadata`: nullable, array

---

### 📦 A r receipts

#### `GET` `api/v1/a-r-receipts`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `receiptDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `receipt_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_receipts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-r-receipts`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `receiptDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `receipt_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_r_receipts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_receipts",
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

#### `GET` `api/v1/a-r-receipts/{a_r_receipt}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `receiptDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `receipt_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_receipts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-r-receipts/{a_r_receipt}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `receiptDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `receipt_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_r_receipts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_receipts",
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

#### `DELETE` `api/v1/a-r-receipts/{a_r_receipt}`

**Campos disponibles:**

- ✅ `contactId` (number) 
- ✅ `receiptDate` (datetime) 🔄
- ✅ `paymentMethod` (string) 🔄
- ✅ `currency` (string) 🔄
- ✅ `amount` (number) 🔄
- ✅ `bankAccountId` (number) 
- ✅ `status` (string) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `contact_id`: required, string
- `receipt_date`: required, date
- `payment_method`: required, string, max:255
- `currency`: nullable, string, max:255
- `amount`: required, string
- `bank_account_id`: required, string
- `status`: required, string, max:255
- `metadata`: nullable, array

---

### 📦 A r invoice receipts

#### `GET` `api/v1/a-r-invoice-receipts`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `arReceiptId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `ar_receipt_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoice_receipts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `POST` `api/v1/a-r-invoice-receipts`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `arReceiptId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `ar_receipt_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "POST",
    "url": "\/api\/v1\/a_r_invoice_receipts",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoice_receipts",
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

#### `GET` `api/v1/a-r-invoice-receipts/{a_r_invoice_receipt}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `arReceiptId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `ar_receipt_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "GET",
    "url": "\/api\/v1\/a_r_invoice_receipts",
    "headers": {
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    }
}
```

---

#### `PATCH` `api/v1/a-r-invoice-receipts/{a_r_invoice_receipt}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `arReceiptId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `ar_receipt_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

**Ejemplo de Request:**

```json
{
    "method": "PATCH",
    "url": "\/api\/v1\/a_r_invoice_receipts\/1",
    "headers": {
        "Content-Type": "application\/vnd.api+json",
        "Accept": "application\/vnd.api+json",
        "Authorization": "Bearer {token}"
    },
    "body": {
        "data": {
            "type": "a_r_invoice_receipts",
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

#### `DELETE` `api/v1/a-r-invoice-receipts/{a_r_invoice_receipt}`

**Campos disponibles:**

- ✅ `arInvoiceId` (number) 
- ✅ `arReceiptId` (number) 
- ✅ `amountApplied` (number) 🔄
- ✅ `appliedAt` (datetime) 🔄
- ✅ `exchangeRateAtApply` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `ar_invoice_id`: required, string
- `ar_receipt_id`: required, string
- `amount_applied`: required, string
- `applied_at`: required, date
- `exchange_rate_at_apply`: nullable, string
- `metadata`: nullable, array

---

### 📦 Purchase orders

#### `GET` `api/v1/purchase-orders`

**Campos disponibles:**

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

---

#### `GET` `api/v1/purchase-orders/reports`

**Campos disponibles:**

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

#### `GET` `api/v1/purchase-orders/suppliers`

**Campos disponibles:**

- ✅ `contact_id` (number) 
- ✅ `contactId` (number) 
- ✅ `orderDate` (datetime) 
- ✅ `status` (string) 
- ✅ `totalAmount` (number) 
- ✅ `notes` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `contact` (relationship)
- `purchaseOrderItems` (relationship[])

**Validaciones:**

- `orderDate`: required, sometimes, date
- `status`: required, sometimes, string, in:pending,approved,received,cancelled
- `totalAmount`: required, sometimes, numeric, min:0
- `notes`: nullable, string
- `contact`: required, sometimes

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

### 📦 Purchase order items

#### `GET` `api/v1/purchase-order-items`

**Campos disponibles:**

- ✅ `purchaseOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `purchaseOrderId`: required, sometimes, exists:purchase_orders,id
- `productId`: required, sometimes, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `subtotal`: sometimes, numeric, min:0
- `total`: sometimes, numeric, min:0
- `metadata`: sometimes, array

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

- ✅ `purchaseOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `purchaseOrderId`: required, sometimes, exists:purchase_orders,id
- `productId`: required, sometimes, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `subtotal`: sometimes, numeric, min:0
- `total`: sometimes, numeric, min:0
- `metadata`: sometimes, array

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

- ✅ `purchaseOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `purchaseOrderId`: required, sometimes, exists:purchase_orders,id
- `productId`: required, sometimes, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `subtotal`: sometimes, numeric, min:0
- `total`: sometimes, numeric, min:0
- `metadata`: sometimes, array

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

- ✅ `purchaseOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `purchaseOrderId`: required, sometimes, exists:purchase_orders,id
- `productId`: required, sometimes, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `subtotal`: sometimes, numeric, min:0
- `total`: sometimes, numeric, min:0
- `metadata`: sometimes, array

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

- ✅ `purchaseOrderId` (number) 🔄
- ✅ `productId` (number) 🔄
- ✅ `quantity` (number) 🔄
- ✅ `unitPrice` (number) 🔄
- ✅ `discount` (number) 🔄
- ✅ `subtotal` (number) 🔄
- ✅ `total` (number) 🔄
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `purchaseOrderId`: required, sometimes, exists:purchase_orders,id
- `productId`: required, sometimes, exists:products,id
- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0
- `discount`: sometimes, numeric, min:0
- `subtotal`: sometimes, numeric, min:0
- `total`: sometimes, numeric, min:0
- `metadata`: sometimes, array

---

### 📦 Sales orders

#### `GET` `api/v1/sales-orders`

**Campos disponibles:**

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
- `discount_total`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000
- `metadata`: nullable, array

---

#### `GET` `api/v1/sales-orders/reports`

**Campos disponibles:**

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

#### `GET` `api/v1/sales-orders/customers`

**Campos disponibles:**

- ✅ `contact_id` (number) 
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

- `contact` (relationship)
- `customer` (relationship)
- `items` (relationship[])

**Validaciones:**

- `order_number`: required, sometimes, string, max:50, sales_orders, order_number
- `status`: required, sometimes, draft, confirmed, processing, shipped, delivered, cancelled
- `order_date`: required, sometimes, date
- `approved_at`: nullable, date
- `delivered_at`: nullable, date
- `total_amount`: required, sometimes, numeric, min:0
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

