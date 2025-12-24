# PageBuilder Module - Frontend Integration Guide

**Module:** PageBuilder
**Entities:** 1 (Page)
**Endpoints:** 5 CRUD
**Base Path:** `/api/v1`

## Overview

The PageBuilder module manages CMS-style pages with HTML, CSS, and JSON content storage. Designed to integrate with visual page builders like GrapesJS. Supports draft/published workflow with public access to published pages.

## Core Entity

### Page

**Endpoint:** `/pages`
**Resource Type:** `pages`

#### TypeScript Interface

```typescript
type PageStatus = 'draft' | 'published' | 'deleted' | 'archived' | 'active' | 'inactive';

interface Page {
  id: string;
  title: string;
  slug: string;
  html: string | null;
  css: string | null;
  json: Record<string, any> | null;  // GrapesJS serialized data
  status: PageStatus;
  publishedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

interface PageCreateRequest {
  title: string;
  slug: string;
  html?: string;
  css?: string;
  json?: Record<string, any>;
  status?: PageStatus;
  publishedAt?: string;
  userId?: string;  // relationship
}

interface PageUpdateRequest {
  title?: string;
  slug?: string;
  html?: string;
  css?: string;
  json?: Record<string, any>;
  status?: PageStatus;
  publishedAt?: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `title` | `title` | string | Yes | Yes | No |
| `slug` | `slug` | string | Yes | No | Yes |
| `html` | `html` | longText | No | No | No |
| `css` | `css` | longText | No | No | No |
| `json` | `json` | array | No | No | No |
| `status` | `status` | string | No | Yes | Yes |
| `publishedAt` | `published_at` | datetime | No | Yes | No |

#### Relationships

- `user` → User (belongsTo) - Page author/owner

---

## API Endpoints

### List Pages

```http
GET /api/v1/pages
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

**Access Control:**
- Users with `page.index` permission: See all pages
- Other users: Only see published pages (where `published_at` is not null)

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `filter[slug]` | `about-us` | Filter by exact slug |
| `filter[status]` | `published` | Filter by status |
| `sort` | `-publishedAt` | Sort by field |
| `page[number]` | `1` | Page number |
| `page[size]` | `15` | Items per page |

#### Response

```json
{
  "data": [
    {
      "type": "pages",
      "id": "1",
      "attributes": {
        "title": "About Us",
        "slug": "about-us",
        "html": "<div class='container'>...</div>",
        "css": ".container { padding: 20px; }",
        "json": {
          "components": [...],
          "styles": [...]
        },
        "status": "published",
        "publishedAt": "2024-01-15T10:00:00Z"
      },
      "relationships": {
        "user": {
          "data": { "type": "users", "id": "1" }
        }
      }
    }
  ]
}
```

---

### Get Page by ID

```http
GET /api/v1/pages/{id}
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

---

### Get Page by Slug

```http
GET /api/v1/pages?filter[slug]=about-us
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

---

### Create Page

```http
POST /api/v1/pages
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
```

```json
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "New Page",
      "slug": "new-page",
      "html": "<h1>Welcome</h1>",
      "css": "h1 { color: blue; }",
      "json": {
        "components": [
          { "type": "text", "content": "Welcome" }
        ]
      },
      "status": "draft"
    },
    "relationships": {
      "user": {
        "data": { "type": "users", "id": "1" }
      }
    }
  }
}
```

---

### Update Page

```http
PATCH /api/v1/pages/{id}
Authorization: Bearer {token}
Content-Type: application/vnd.api+json
```

```json
{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "title": "Updated Title",
      "status": "published",
      "publishedAt": "2024-01-20T12:00:00Z"
    }
  }
}
```

---

### Delete Page

```http
DELETE /api/v1/pages/{id}
Authorization: Bearer {token}
```

Performs soft delete. Returns `204 No Content`.

---

## TypeScript Service

```typescript
interface PageResource {
  type: 'pages';
  id: string;
  attributes: {
    title: string;
    slug: string;
    html: string | null;
    css: string | null;
    json: Record<string, any> | null;
    status: string;
    publishedAt: string | null;
  };
  relationships?: {
    user?: {
      data: { type: 'users'; id: string } | null;
    };
  };
}

interface CreatePageData {
  title: string;
  slug: string;
  html?: string;
  css?: string;
  json?: Record<string, any>;
  status?: 'draft' | 'published';
  publishedAt?: string;
  userId?: string;
}

interface UpdatePageData {
  title?: string;
  slug?: string;
  html?: string;
  css?: string;
  json?: Record<string, any>;
  status?: string;
  publishedAt?: string;
}

class PageBuilderService {
  private baseUrl = '/api/v1/pages';

  async list(params?: {
    status?: string;
    sort?: string;
    page?: number;
    perPage?: number;
  }): Promise<{ data: PageResource[]; meta: any }> {
    const queryParams = new URLSearchParams();

    if (params?.status) queryParams.set('filter[status]', params.status);
    if (params?.sort) queryParams.set('sort', params.sort);
    if (params?.page) queryParams.set('page[number]', params.page.toString());
    if (params?.perPage) queryParams.set('page[size]', params.perPage.toString());

    const response = await fetch(`${this.baseUrl}?${queryParams.toString()}`, {
      headers: this.getHeaders(),
    });
    return response.json();
  }

  async get(id: string): Promise<PageResource> {
    const response = await fetch(`${this.baseUrl}/${id}`, {
      headers: this.getHeaders(),
    });
    const result = await response.json();
    return result.data;
  }

  async getBySlug(slug: string): Promise<PageResource | null> {
    const response = await fetch(`${this.baseUrl}?filter[slug]=${encodeURIComponent(slug)}`, {
      headers: this.getHeaders(),
    });
    const result = await response.json();
    return result.data[0] || null;
  }

  async create(data: CreatePageData): Promise<PageResource> {
    const payload: any = {
      data: {
        type: 'pages',
        attributes: {
          title: data.title,
          slug: data.slug,
          html: data.html || null,
          css: data.css || null,
          json: data.json || null,
          status: data.status || 'draft',
          publishedAt: data.publishedAt || null,
        },
      },
    };

    if (data.userId) {
      payload.data.relationships = {
        user: { data: { type: 'users', id: data.userId } },
      };
    }

    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    return result.data;
  }

  async update(id: string, data: UpdatePageData): Promise<PageResource> {
    const attributes: any = {};
    if (data.title !== undefined) attributes.title = data.title;
    if (data.slug !== undefined) attributes.slug = data.slug;
    if (data.html !== undefined) attributes.html = data.html;
    if (data.css !== undefined) attributes.css = data.css;
    if (data.json !== undefined) attributes.json = data.json;
    if (data.status !== undefined) attributes.status = data.status;
    if (data.publishedAt !== undefined) attributes.publishedAt = data.publishedAt;

    const response = await fetch(`${this.baseUrl}/${id}`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify({
        data: { type: 'pages', id, attributes },
      }),
    });
    const result = await response.json();
    return result.data;
  }

  async delete(id: string): Promise<void> {
    await fetch(`${this.baseUrl}/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });
  }

  async publish(id: string): Promise<PageResource> {
    return this.update(id, {
      status: 'published',
      publishedAt: new Date().toISOString(),
    });
  }

  async unpublish(id: string): Promise<PageResource> {
    return this.update(id, {
      status: 'draft',
      publishedAt: null,
    });
  }

  private getHeaders(): Record<string, string> {
    return {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json',
    };
  }
}

export const pageBuilderService = new PageBuilderService();
```

---

## GrapesJS Integration

### Saving GrapesJS Data

```typescript
import grapesjs from 'grapesjs';
import { pageBuilderService } from './services/page-builder.service';

const editor = grapesjs.init({
  container: '#editor',
  // ... other config
});

async function savePage(pageId: string) {
  const html = editor.getHtml();
  const css = editor.getCss();
  const json = editor.getProjectData();

  await pageBuilderService.update(pageId, {
    html,
    css,
    json,
  });
}
```

### Loading GrapesJS Data

```typescript
async function loadPage(pageId: string) {
  const page = await pageBuilderService.get(pageId);

  if (page.attributes.json) {
    // Load from JSON project data (recommended)
    editor.loadProjectData(page.attributes.json);
  } else if (page.attributes.html) {
    // Fallback to HTML/CSS
    editor.setComponents(page.attributes.html);
    editor.setStyle(page.attributes.css || '');
  }
}
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `page.index` | List all pages (including drafts) | god, admin, tech |
| `page.show` | View page details | god, admin, tech |
| `page.store` | Create pages | god, admin |
| `page.update` | Update pages | god, admin |
| `page.destroy` | Delete pages | god, admin |

**Note:** Users without `page.index` can only see published pages.

---

## Validation Rules

| Field | Rules |
|-------|-------|
| title | required, string, max:255 |
| slug | required, string, max:255, unique:pages,slug |
| html | nullable, string |
| css | nullable, string |
| json | nullable, array |
| status | sometimes, in:draft,published,deleted,archived,active,inactive |
| publishedAt | nullable, date |
| user | valid JSON:API toOne relationship |

---

## Page Rendering

### Render Page HTML

```tsx
interface PageRendererProps {
  page: {
    html: string | null;
    css: string | null;
  };
}

export function PageRenderer({ page }: PageRendererProps) {
  return (
    <>
      {page.css && <style>{page.css}</style>}
      {page.html && (
        <div
          className="page-content"
          dangerouslySetInnerHTML={{ __html: page.html }}
        />
      )}
    </>
  );
}
```

### Public Page Route

```tsx
import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { pageBuilderService } from './services/page-builder.service';

export function PublicPage() {
  const { slug } = useParams<{ slug: string }>();
  const [page, setPage] = useState<PageResource | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    loadPage();
  }, [slug]);

  async function loadPage() {
    if (!slug) return;

    try {
      const page = await pageBuilderService.getBySlug(slug);
      if (page && page.attributes.status === 'published') {
        setPage(page);
      } else {
        setNotFound(true);
      }
    } catch {
      setNotFound(true);
    } finally {
      setLoading(false);
    }
  }

  if (loading) return <div>Loading...</div>;
  if (notFound) return <div>Page not found</div>;
  if (!page) return null;

  return (
    <div className="public-page">
      <h1>{page.attributes.title}</h1>
      <PageRenderer page={page.attributes} />
    </div>
  );
}
```

---

## Admin Page List

```tsx
import { useState, useEffect } from 'react';
import { pageBuilderService, PageResource } from './services/page-builder.service';

export function PageList() {
  const [pages, setPages] = useState<PageResource[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadPages();
  }, []);

  async function loadPages() {
    const result = await pageBuilderService.list({ sort: '-publishedAt' });
    setPages(result.data);
    setLoading(false);
  }

  async function handlePublish(page: PageResource) {
    if (page.attributes.status === 'published') {
      await pageBuilderService.unpublish(page.id);
    } else {
      await pageBuilderService.publish(page.id);
    }
    loadPages();
  }

  async function handleDelete(id: string) {
    if (confirm('Delete this page?')) {
      await pageBuilderService.delete(id);
      loadPages();
    }
  }

  if (loading) return <div>Loading...</div>;

  return (
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Slug</th>
          <th>Status</th>
          <th>Published</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {pages.map(page => (
          <tr key={page.id}>
            <td>{page.attributes.title}</td>
            <td>/{page.attributes.slug}</td>
            <td>{page.attributes.status}</td>
            <td>{page.attributes.publishedAt || '—'}</td>
            <td>
              <button onClick={() => handlePublish(page)}>
                {page.attributes.status === 'published' ? 'Unpublish' : 'Publish'}
              </button>
              <a href={`/admin/pages/${page.id}/edit`}>Edit</a>
              <button onClick={() => handleDelete(page.id)}>Delete</button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```
