# API Documentation

**Generado:** 2025-08-07T07:28:00.056128Z

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
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 🔒
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒
- ✅ `deleted_at` (datetime) 🔒

**Relaciones disponibles:**

- `roles` (relationship[])

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
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 🔒
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒
- ✅ `deleted_at` (datetime) 🔒

**Relaciones disponibles:**

- `roles` (relationship[])

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
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 🔒
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒
- ✅ `deleted_at` (datetime) 🔒

**Relaciones disponibles:**

- `roles` (relationship[])

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
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 🔒
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒
- ✅ `deleted_at` (datetime) 🔒

**Relaciones disponibles:**

- `roles` (relationship[])

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
- ✅ `password` (string) 
- ✅ `password_confirmation` (string) 
- ✅ `email_verified_at` (datetime) 🔒
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒
- ✅ `deleted_at` (datetime) 🔒

**Relaciones disponibles:**

- `roles` (relationship[])

**Validaciones:**

- `name`: required, string, max:255
- `email`: required, email, max:255, users, email
- `status`: required, active, inactive, banned

---

### 📦 Audits

#### `GET` `api/v1/audits`

**Campos disponibles:**

- ✅ `event` (string) 🔄
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

- ✅ `event` (string) 🔄
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

- ✅ `event` (string) 🔄
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

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
- ✅ `causer` (mixed) 
- ✅ `event` (mixed) 

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
- ✅ `slug` (mixed) 
- ✅ `status` (mixed) 

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published
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
- ✅ `slug` (mixed) 
- ✅ `status` (mixed) 

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published
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
- ✅ `slug` (mixed) 
- ✅ `status` (mixed) 

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published
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
- ✅ `slug` (mixed) 
- ✅ `status` (mixed) 

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published
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
- ✅ `slug` (mixed) 
- ✅ `status` (mixed) 

**Relaciones disponibles:**

- `user` (relationship)

**Validaciones:**

- `title`: required, string, max:255
- `slug`: required, string, max:255, pages, slug
- `html`: nullable, string
- `css`: nullable, string
- `json`: nullable, array
- `status`: sometimes, string, in:draft,published
- `publishedAt`: nullable, date

---

### 📦 Roles

#### `GET` `api/v1/roles`

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
- ✅ `guard_name` (string) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄

**Validaciones:**

- `name`: required, string, max:255
- `guard_name`: required, string

---

#### `GET` `api/v1/roles/{role}/permissions`

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

---

### 📦 Units

#### `GET` `api/v1/units`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

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
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

---

### 📦 Categories

#### `GET` `api/v1/categories`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

---

### 📦 Brands

#### `GET` `api/v1/brands`

**Campos disponibles:**

- ✅ `name` (string) 🔄
- ✅ `description` (string) 
- ✅ `slug` (string) 🔄
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

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
- ✅ `created_at` (datetime) 🔒🔄
- ✅ `updated_at` (datetime) 🔒

**Relaciones disponibles:**

- `products` (relationship[])

**Validaciones:**

- `name`: required, string, max:255, brands, name
- `description`: nullable, string, max:500

---

### 📦 Warehouses

#### `GET` `api/v1/warehouses`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

### 📦 Warehouse locations

#### `GET` `api/v1/warehouse-locations`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

### 📦 Product batches

#### `GET` `api/v1/product-batches`

**Campos disponibles:**

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

- ✅ `status` (string) 
- ✅ `certifications` (object) 
- ✅ `metadata` (object) 
- ✅ `createdAt` (datetime) 
- ✅ `updatedAt` (datetime) 
- ✅ `status` (mixed) 
- ✅ `batch_number` (mixed) 
- ✅ `lot_number` (mixed) 
- ✅ `product_id` (mixed) 
- ✅ `warehouse_id` (mixed) 
- ✅ `warehouse_location_id` (mixed) 

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

### 📦 Shopping carts

#### `GET` `api/v1/shopping-carts`

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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
- ✅ `status` (mixed) 

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

### 📦 Suppliers

#### `GET` `api/v1/suppliers`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

---

### 📦 Purchase orders

#### `GET` `api/v1/purchase-orders`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

**Relaciones disponibles:**

- `purchaseOrder` (relationship)
- `product` (relationship)

**Validaciones:**

- `quantity`: required, numeric, min:0.01
- `unitPrice`: required, numeric, min:0

---

### 📦 Purchase order items

#### `GET` `api/v1/purchase-order-items`

**Campos disponibles:**

- ✅ `quantity` (number) 
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `subtotal` (number) 
- ✅ `createdAt` (datetime) 🔒🔄
- ✅ `updatedAt` (datetime) 🔒🔄
- ✅ `quantity` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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

### 📦 Sales order items

#### `GET` `api/v1/sales-order-items`

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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
- ✅ `name` (mixed) 
- ✅ `email` (mixed) 
- ✅ `classification` (mixed) 
- ✅ `is_active` (mixed) 

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

