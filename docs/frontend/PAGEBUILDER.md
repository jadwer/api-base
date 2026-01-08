# PageBuilder Module

## Overview

Simple CMS for creating static pages (about, terms, privacy, etc.).

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Page | `/api/v1/pages` | CMS pages |

## Page

```typescript
type PageStatus = 'draft' | 'published' | 'archived';

interface Page {
  id: string;
  title: string;
  slug: string;             // URL-friendly: 'about-us'
  content: string;          // HTML content
  metaTitle: string | null;
  metaDescription: string | null;
  status: PageStatus;
  publishedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

// List pages
GET /api/v1/pages

// List published pages only
GET /api/v1/pages?filter[status]=published

// Get page by ID
GET /api/v1/pages/{id}

// Get page by slug (public)
GET /api/public/v1/pages/{slug}

// Create page
POST /api/v1/pages
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "About Us",
      "slug": "about-us",
      "content": "<h1>About Our Company</h1><p>We are...</p>",
      "metaTitle": "About Us | Company Name",
      "metaDescription": "Learn about our company history...",
      "status": "draft"
    }
  }
}

// Update page
PATCH /api/v1/pages/{id}
{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "content": "<h1>Updated content</h1>",
      "status": "published",
      "publishedAt": "2026-01-08T10:00:00Z"
    }
  }
}

// Delete page
DELETE /api/v1/pages/{id}
```

## Public Access (No Auth)

```typescript
// Get published page by slug (for public website)
GET /api/public/v1/pages/about-us

// Response
{
  "data": {
    "type": "pages",
    "id": "1",
    "attributes": {
      "title": "About Us",
      "slug": "about-us",
      "content": "<h1>About Our Company</h1>...",
      "metaTitle": "About Us | Company Name",
      "metaDescription": "Learn about our company..."
    }
  }
}
```

## Common Pages

| Slug | Purpose |
|------|---------|
| `about-us` | Company information |
| `terms-of-service` | Terms and conditions |
| `privacy-policy` | Privacy policy |
| `contact` | Contact information |
| `faq` | Frequently asked questions |
| `shipping-policy` | Shipping information |
| `return-policy` | Return/refund policy |

## Filters

| Filter | Example |
|--------|---------|
| `filter[status]` | `?filter[status]=published` |
| `filter[slug]` | `?filter[slug]=about-us` |
| `filter[title]` | `?filter[title]=About` |

## Status Flow

```
draft → published → archived
  ↑         ↓
  └─────────┘ (unpublish)
```

## Permissions

| Action | god | admin | tech | customer |
|--------|-----|-------|------|----------|
| List | Yes | Yes | Yes | No |
| View | Yes | Yes | Yes | Public* |
| Create | Yes | Yes | No | No |
| Update | Yes | Yes | No | No |
| Delete | Yes | Yes | No | No |

*Published pages available via public endpoint

## Frontend Implementation

```typescript
// Admin: Page editor
async function savePage(page: Partial<Page>) {
  const method = page.id ? 'PATCH' : 'POST';
  const url = page.id ? `/api/v1/pages/${page.id}` : '/api/v1/pages';

  await fetch(url, {
    method,
    headers,
    body: JSON.stringify({
      data: {
        type: 'pages',
        ...(page.id && { id: page.id }),
        attributes: {
          title: page.title,
          slug: page.slug,
          content: page.content,
          metaTitle: page.metaTitle,
          metaDescription: page.metaDescription,
          status: page.status
        }
      }
    })
  });
}

// Public: Render page
async function getPublicPage(slug: string) {
  const response = await fetch(`/api/public/v1/pages/${slug}`);
  if (!response.ok) return null;
  const { data } = await response.json();
  return data.attributes;
}

// React component
function PublicPage({ slug }: { slug: string }) {
  const [page, setPage] = useState(null);

  useEffect(() => {
    getPublicPage(slug).then(setPage);
  }, [slug]);

  if (!page) return <NotFound />;

  return (
    <>
      <Head>
        <title>{page.metaTitle || page.title}</title>
        <meta name="description" content={page.metaDescription} />
      </Head>
      <article dangerouslySetInnerHTML={{ __html: page.content }} />
    </>
  );
}
```

## SEO Considerations

- Always set `metaTitle` and `metaDescription`
- Use semantic HTML in content
- Slugs should be lowercase, hyphenated
- Include Open Graph tags in frontend
